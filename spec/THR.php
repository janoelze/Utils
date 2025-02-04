<?php


require_once __DIR__ . '/../src/THR.php';
require_once __DIR__ . '/../src/LG.php';

use JanOelze\Utils\THR;
use JanOelze\Utils\LG;

$lg = new LG([
    'date_format' => 'd-m-Y H:i:s',
    'colors' => true,
    'destinations' => ['console']
]);

// Create a THR instance with a maximum of 3 concurrent workers.
$pool = new THR([
    'max_workers' => 3,
]);

// Define a task that processes input data and returns a result.
$task = function ($input) {
    sleep(1);
    return [
        'input' => $input,
        'result' => $input * 2,
    ];
};

// Submit tasks to the pool.
foreach (range(1, 10) as $i) {
    $pool->submit($task, $i);
}

// Wait for all tasks to complete and collect their results.
$results = $pool->wait();

// Print out the results.
foreach ($results as $pid => $result) {
    $lg->log("PID: $pid", json_encode($result));
}

$lg->success('All tasks completed.');
