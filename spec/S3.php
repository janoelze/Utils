<?php

require_once __DIR__ . '/../src/S3.php';

use JanOelze\Utils\S3;

// S3 is a simple class for working with Amazon S3 buckets.
$s3 = new S3([
  'bucket' => 's3-drive-2024-11-28',
  'region' => 'eu-central-1',
  'access_key' => getenv('AWS_ACCESS_KEY_ID'),
  'secret_key' => getenv('AWS_SECRET_KEY'),
]);

// List all files in the bucket
$files = $s3->listFiles();

// List all files in the bucket with glob pattern
$files = $s3->listFiles('*.txt');

$file_name = "/tmp/key.txt";
$secret_key = rand(1000, 9999);

// Create a test file
file_put_contents($file_name, $secret_key);

// Upload a file to the bucket
$s3->upload($file_name, 'key.txt');

// // Download a file from the bucket
// $s3->download('key.txt', '/tmp/key-downloaded.txt');

// // Check if the file exists in the bucket
// if ($s3->exists('key.txt')) {
//   echo 'File exists';
// } else {
//   echo 'File does not exist';
// }

// Read and output the file content straight from the bucket
$contents = $s3->read('key.txt');

print $contents;

// // Remove the test file
// unlink('/tmp/notes.txt');

// // Remove a file from the bucket
// $s3->remove('notes.txt');

// // Remove all files from the bucket
// $s3->removeAll();

// try {
//   // Set permissions for a file in the bucket
//   $s3->setPermissions('notes.txt', 'public-read');
// } catch (Exception $e) {
//   // uh-oh, something went wrong
//   echo 'Error: ' . $e->getMessage();
// }

// // Get the URL for a file in the bucket
$url = $s3->getUrl('notes.txt');
// print_r($url);