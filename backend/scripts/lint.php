<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$count = 0;
$failed = false;
foreach (['public', 'src', 'scripts', 'tests'] as $directory) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        passthru(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()), $code);
        $failed = $failed || $code !== 0;
        $count++;
    }
}
echo "Checked {$count} PHP files.\n";
exit($failed ? 1 : 0);
