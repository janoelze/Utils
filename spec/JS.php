<?php

declare(ticks=1);

require_once __DIR__ . '/../src/JS.php';
use JanOelze\Utils\JS;

// JS is a JSON Store that reads and writes data to a file.
// $js = new JS();

// =====================
// Test Script
// =====================

// File to be used by the JSON store. Remove any previous test file.
$storePath = __DIR__ . '/data.json';
if (file_exists($storePath)) {
  unlink($storePath);
}

// Number of child processes and iterations per process
$processes = 100;
$iterations = 100;

// An array to hold child process IDs
$children = [];

echo "Spawning {$processes} child processes with {$iterations} iterations each.\n";

// Fork child processes
for ($i = 0; $i < $processes; $i++) {
  $pid = pcntl_fork();
  if ($pid == -1) {
    // Fork failed
    die("Could not fork process $i.\n");
  } elseif ($pid) {
    // Parent process: record the child's PID
    $children[] = $pid;
  } else {
    // Child process
    $store = new JS($storePath);
    for ($j = 0; $j < $iterations; $j++) {
      // Randomly choose an operation: set, delete, or get
      $action = rand(1, 3);
      // Use a key space of 50 keys
      $key = "key_" . rand(1, 50);

      try {
        switch ($action) {
          case 1:
            // Set a random value
            $value = [
              'process'   => getmypid(),
              'iteration' => $j,
              'random'    => rand(1000, 9999)
            ];
            $store->set($key, $value);
            // Uncomment the following line to see detailed output:
            // echo "[Child " . getmypid() . "] Set {$key}: " . json_encode($value) . "\n";
            break;
          case 2:
            // Delete the key
            $store->delete($key);
            // echo "[Child " . getmypid() . "] Deleted {$key}\n";
            break;
          case 3:
            // Retrieve the key
            $result = $store->get($key);
            // echo "[Child " . getmypid() . "] Get {$key}: " . json_encode($result) . "\n";
            break;
        }
      } catch (Exception $e) {
        // Catch and report any errors
        echo "[Child " . getmypid() . "] Error: " . $e->getMessage() . "\n";
      }

      // Sleep a random short time (between 1ms and 10ms) to simulate work
      usleep(rand(1000, 10000));
    }
    exit(0); // Child exits after completing its iterations
  }
}

// Parent process: wait for all children to finish
foreach ($children as $childPid) {
  pcntl_waitpid($childPid, $status);
}

// Final consistency check
$store = new JS($storePath);
$data = $store->getAll();

echo "Final data in store:\n";
print_r($data);
