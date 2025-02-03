<?php

require_once __DIR__ . '/../src/CON.php';
require_once __DIR__ . '/../src/LG.php';

// CON is a simple wrapper around SSH2
use JanOelze\Utils\CON;
use JanOelze\Utils\LG;

$lg = new LG([
  'date_format' => 'd-m-Y H:i:s',
  'colors' => true,
  'destinations' => [
    'console',
  ]
]);

// Create a new connection
$con = new CON([
  'protocol' => 'ssh',
  'host' => 'aquila.uberspace.de',
  // 'port' => 22, // Optional
  'username' => 'janoelze',
  // 'password' => 'password', // Optional
  'timeout' => 10, // Optional
  'key' => '/Users/janoelze/.ssh/id_rsa', // Optional
]);

// Execute a command
$res = $con->exec('ls -l');

// Check for errors
if ($res['exit_code'] !== 0) {
  $lg->error('Error: ' . $res['stderr']);
  exit;
}

// Output the result
$lg->log($res['stdout']);

// Upload a file
$res = $con->upload(__DIR__ . '/test.txt', '/var/www/virtual/janoelze/html/test.txt');

if ($res['exit_code'] !== 0) {
  $lg->error('Error: ' . $res['stderr']);
  exit;
} else {
  $lg->success('File uploaded successfully.');
}

// // Download the PHP error log
$con->download('/var/www/virtual/janoelze/html/test.txt', __DIR__ . '/test-down.txt');

// Confirm the output is the same
if (md5_file(__DIR__ . '/test.txt') === md5_file(__DIR__ . '/test-down.txt')) {
  $lg->success('File download successful.');
} else {
  $lg->error('File download failed.');
}

// Upload a directory
$con->upload(__DIR__ . '/test-dir', '/var/www/virtual/janoelze/html/test/test-dir');

// Get free disk space
$res = $con->exec('df -h /var/www/virtual/janoelze/html');

if ($res['exit_code'] !== 0) {
  $lg->error('Error: ' . $res['stderr']);
  exit;
} else {
  $lg->log($res['stdout']);
}

// Close the connection
$con->close();
