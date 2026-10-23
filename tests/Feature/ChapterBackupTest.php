<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ChapterBackupTest extends TestCase
{
    public function test_sqlite_backup_copies_private_files_and_detects_tampering(): void
    {
        $sandbox = sys_get_temp_dir().'/chapter-backup-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($sandbox.'/private');
        file_put_contents($sandbox.'/private/document.txt', 'private chapter record');
        file_put_contents($sandbox.'/source.sqlite', '');

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', $sandbox.'/source.sqlite');
        config()->set('chapter.backup_path', $sandbox.'/backups');
        config()->set('chapter.backup_private_path', $sandbox.'/private');
        DB::purge('sqlite');

        try {
            DB::statement('create table backup_probe (id integer primary key, name text)');
            DB::table('backup_probe')->insert(['name' => 'Carmona']);
            $this->assertSame(0, Artisan::call('chapter:backup'));

            $backups = File::directories($sandbox.'/backups');
            $this->assertCount(1, $backups);
            $backup = $backups[0];
            $this->assertFileExists($backup.'/database.sqlite');
            $this->assertSame('private chapter record', file_get_contents($backup.'/private/document.txt'));
            $this->assertSame(0, Artisan::call('chapter:backup', ['--verify' => $backup]));

            file_put_contents($backup.'/private/document.txt', 'tampered');
            $this->assertSame(1, Artisan::call('chapter:backup', ['--verify' => $backup]));
        } finally {
            DB::disconnect('sqlite');
            File::deleteDirectory($sandbox);
        }
    }
}
