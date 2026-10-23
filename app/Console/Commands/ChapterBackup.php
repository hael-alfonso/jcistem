<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\Process;

class ChapterBackup extends Command
{
    protected $signature = 'chapter:backup {--verify= : Verify an existing backup directory}';

    protected $description = 'Back up or verify the chapter database and private files';

    public function handle(): int
    {
        try {
            $root = $this->backupRoot();
            if ($path = $this->option('verify')) {
                $this->verify($root, $path);
                $this->info('Backup verified.');
                return self::SUCCESS;
            }

            $folder = $root.DIRECTORY_SEPARATOR.now()->format('Y-m-d_H-i-s').'_'.bin2hex(random_bytes(4));
            if (!mkdir($folder, 0700)) throw new RuntimeException('Cannot create backup directory.');
            $this->snapshotDatabase($folder);
            $this->copyPrivateFiles($folder);
            $manifest = ['created_at' => now()->toIso8601String(), 'database_driver' => DB::getDriverName(), 'files' => []];
            foreach ($this->filesIn($folder) as $relative => $file) {
                $manifest['files'][$relative] = ['size' => filesize($file), 'sha256' => hash_file('sha256', $file)];
            }
            $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            file_put_contents($folder.DIRECTORY_SEPARATOR.'manifest.json', $json, LOCK_EX);
            file_put_contents($folder.DIRECTORY_SEPARATOR.'manifest.hmac', hash_hmac('sha256', $json, config('app.key')), LOCK_EX);
            $this->verify($root, $folder);
            $this->info('Backup created and verified: '.$folder);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            report($e);
            $this->error('Backup failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }

    private function backupRoot(): string
    {
        $root = config('chapter.backup_path');
        if (!is_string($root) || trim($root) === '') {
            throw new RuntimeException('Set CHAPTER_BACKUP_PATH to a private directory outside the application before running backups.');
        }
        if (!is_dir($root) && !mkdir($root, 0700, true) && !is_dir($root)) {
            throw new RuntimeException('Cannot create backup root.');
        }
        $root = realpath($root);
        foreach ([base_path(), public_path(), storage_path('app/public'), config('chapter.backup_private_path')] as $unsafe) {
            $unsafe = realpath($unsafe);
            if ($unsafe && ($root === $unsafe || str_starts_with($root, $unsafe.DIRECTORY_SEPARATOR))) {
                throw new RuntimeException('Backup path must be outside the application and private upload directories.');
            }
        }
        if (!config('app.key')) throw new RuntimeException('APP_KEY is required to authenticate backup manifests.');
        return $root;
    }

    private function snapshotDatabase(string $folder): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $pdo = DB::connection()->getPdo();
            $pdo->exec('VACUUM INTO '.$pdo->quote($folder.DIRECTORY_SEPARATOR.'database.sqlite'));
            return;
        }
        if ($driver !== 'mysql') throw new RuntimeException('Only MySQL and SQLite backups are supported.');

        $config = DB::connection()->getConfig();
        $binary = config('chapter.mysqldump_path');
        $command = [$binary, '--single-transaction', '--quick', '--no-tablespaces',
            '--host='.$config['host'], '--port='.($config['port'] ?? 3306), '--user='.$config['username'],
            '--result-file='.$folder.DIRECTORY_SEPARATOR.'database.sql', $config['database']];
        $process = new Process($command, null, ['MYSQL_PWD' => (string) ($config['password'] ?? '')]);
        $process->setTimeout(null);
        $process->run();
        if (!$process->isSuccessful()) throw new RuntimeException('mysqldump failed; check database access and binary path.');
    }

    private function copyPrivateFiles(string $folder): void
    {
        $source = config('chapter.backup_private_path');
        if (!is_dir($source)) return;
        $target = $folder.DIRECTORY_SEPARATOR.'private';
        mkdir($target, 0700);
        foreach ($this->filesIn($source) as $relative => $file) {
            $destination = $target.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $directory = dirname($destination);
            if (!is_dir($directory) && !mkdir($directory, 0700, true)) throw new RuntimeException('Cannot create backup subdirectory.');
            if (!copy($file, $destination)) throw new RuntimeException('Cannot copy private file: '.$relative);
        }
    }

    private function filesIn(string $root): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            if ($entry->isLink()) throw new RuntimeException('Symlinks are not allowed in backup contents.');
            if (!$entry->isFile()) continue;
            $relative = str_replace('\\', '/', substr($entry->getPathname(), strlen($root) + 1));
            $files[$relative] = $entry->getPathname();
        }
        ksort($files);
        return $files;
    }

    private function verify(string $root, string $path): void
    {
        $path = realpath($path);
        if (!$path || !str_starts_with($path, $root.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Backup directory must be inside the configured backup root.');
        }
        $json = @file_get_contents($path.DIRECTORY_SEPARATOR.'manifest.json');
        $signature = @file_get_contents($path.DIRECTORY_SEPARATOR.'manifest.hmac');
        if ($json === false || $signature === false || !hash_equals(hash_hmac('sha256', $json, config('app.key')), trim($signature))) {
            throw new RuntimeException('Backup manifest signature is invalid.');
        }
        $manifest = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        foreach ($manifest['files'] ?? [] as $relative => $expected) {
            if (str_contains($relative, '..') || str_starts_with($relative, '/')) throw new RuntimeException('Unsafe backup path.');
            $file = $path.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if (!is_file($file) || filesize($file) !== $expected['size'] || !hash_equals($expected['sha256'], hash_file('sha256', $file))) {
                throw new RuntimeException('Backup file failed verification: '.$relative);
            }
        }
        $database = $manifest['files']['database.sql'] ?? $manifest['files']['database.sqlite'] ?? null;
        if (!is_array($database) || ($database['size'] ?? 0) <= 0) {
            throw new RuntimeException('Backup has no database snapshot.');
        }
    }
}
