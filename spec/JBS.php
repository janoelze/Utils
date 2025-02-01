<?php

require_once __DIR__ . '/../../src/VLD.php';

use JanOelze\Utils\JBS;

// JBS is a simple library to schedule and execute jobs
$jbs = new JBS([
  'db' => './jobs.sqlite',
  'retention' => 60 * 60 * 24 * 7, // 1 week
]);

// You can schedule a job to run every 30 seconds
$jbs->schedule('30s', 'fetch-news', function () {
  echo "This job runs every 30 seconds\n";
});

// You can schedule a job to run every minute
$jbs->schedule('1m', 'fetch-news', function () {
  echo "This job runs every minute\n";
});

// You can also schedule a job to run every hour
$jbs->schedule('1h', 'fetch-news', function () {
  echo "This job runs every hour\n";
});

// You can also schedule a job to run every day
$jbs->schedule('1d', 'fetch-news', function () {
  echo "This job runs every day\n";
});

// You can also schedule a job to run every week
$jbs->schedule('1w', 'fetch-news', function () {
  echo "This job runs every week\n";
});

// In the background, JBS will manage an sqlite database to keep track of the jobs
// The schema of the database is as follows:
// CREATE TABLE runs (
//   id INTEGER PRIMARY KEY AUTOINCREMENT,
//   job_id TEXT NOT NULL,
//   scheduled_at TEXT NOT NULL,
//   executed_at TEXT,
//   status TEXT NOT NULL,
//   output TEXT
// );

// You can run the jobs by calling the run method, usually triggered by a cron job
// JBS will ensure that the jobs are executed at the scheduled times, and only one instance of each job is running at a time
$jbs->run();

// An onFailure callback can be provided to handle failed jobs
$jbs->onFailure(function ($jobId, $output) {
  echo "Job $jobId failed with output: $output\n";
});

// If a job fails, JBS will retry it up to 3 times
// If a job fails 3 times, JBS will mark it as failed and stop retrying
// You can see the status of the jobs in the database

// You can also manually run a job by calling the runJob method
// This is useful for testing or debugging
$jbs->runJob('fetch-news');

// You can clear all registered jobs by calling the clear method
$jbs->clear();

$jbs->schedule('30s', 'fetch-news', function () {
  echo "This job runs every 30 seconds\n";
});

// Jobs that never ran before will run immediately
$jbs->run();

// You can set the interval to false, the job will run when runJob() is called
$jbs->schedule(false, 'fetch-news', function () {
  echo "I will run when runJob() is called\n";
});
