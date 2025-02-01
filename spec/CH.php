<?php

use JanOelze\Utils\CH;

$cache = new CH([
  'dir' => '/tmp/cache',
]);

// Store a value for 10 minutes, using a numeric time format
$cache->set('username', 'Jan', 600);

// Store a value for 1 day, using a human-readable time format
$cache->set('username', 'Jan', '1d');

// Retrieve the value
echo $cache->get('username'); // Output: Jan

// Array/Object values are also supported
$cache->set('user', ['name' => 'Jan', 'age' => 30], '1d');

// Retrieve the value, it is restored as the original type
echo $cache->get('user')['name']; // Output: Jan

// Clear a specific cache entry
$cache->clear('username');

// Clear all cache entries
$cache->clear();
