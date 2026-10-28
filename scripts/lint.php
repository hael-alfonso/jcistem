<?php

$base = dirname(__DIR__);
$failed = false;
$count = 0;
foreach (['app', 'config', 'database', 'routes', 'tests'] as $directory) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base.'/'.$directory, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') continue;
        $output = [];
        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file->getPathname()).' 2>&1', $output, $status);
        $count++;
        if ($status !== 0) {
            $failed = true;
            echo implode(PHP_EOL, $output).PHP_EOL;
        }
    }
}
echo $count.' PHP files checked.'.PHP_EOL;
exit($failed ? 1 : 0);
