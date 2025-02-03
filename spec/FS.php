<?php

require_once __DIR__ . '/../src/FS.php';

use JanOelze\Utils\FS;

// Instantiate the FS class
$fs = new FS();

// List all files in /tmp matching a specific pattern
$files = $fs->listFiles('/tmp/*.*');

// List all .txt files in the notes directory
$notes = $fs->listFiles('./notes/*.txt');

// Copy a file from notes to /tmp
$fs->copy('./notes/notes.txt', '/tmp/notes.txt');

// Create a new nested directory in /tmp with full permissions
$fs->createDirectory('/tmp/a/b/c', 0777);

// Copy directory /tmp/a to /tmp/x
$fs->copy('/tmp/a', '/tmp/x');

// Remove directories recursively
$fs->remove('/tmp/a');
$fs->remove('/tmp/x');

// Write "Hello, world!" to a file in /tmp (overwrites if exists)
$fs->write('/tmp/hello.txt', 'Hello, world!');

// Append " Goodbye, world!" to the file
$fs->append('/tmp/hello.txt', ' Goodbye, world!');

// Read and output the file content
echo $fs->read('/tmp/hello.txt');

// Remove the file
$fs->remove('/tmp/hello.txt');

// Example usage of zip()
$fs = new \JanOelze\Utils\FS();
$testDir = '/tmp/spec_zip_dir';
$zipPath = '/tmp/spec_archive.zip';

// Setup a directory with sample file
if (!is_dir($testDir)) {
    mkdir($testDir, 0777, true);
}
file_put_contents($testDir . '/sample.txt', 'Sample content');

// Zip the directory
$fs->zip($testDir, $zipPath);

// Validate the zip file exists
if (file_exists($zipPath)) {
    echo "Zip created successfully.\n";
}

// Clean up
unlink($zipPath);
array_map('unlink', glob("$testDir/*.*"));
rmdir($testDir);