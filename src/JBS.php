<?php

namespace JanOelze\Utils;

use PDO;
use PDOException;

class JBS
{
  /**
   * @var PDO
   */
  private $db;

  /**
   * Registered jobs, keyed by job id.
   * Each job is an array with keys: 
   *  - job_id (string)
   *  - interval (int seconds or false)
   *  - callback (callable)
   *
   * @var array
   */
  private $jobs = [];

  /**
   * The failure callback.
   *
   * @var callable|null
   */
  private $failureCallback;

  /**
   * Retention period in seconds.
   *
   * @var int
   */
  private $retention;

  /**
   * Constructor.
   *
   * Options:
   *   - db: the SQLite database file path (default: './jobs.sqlite')
   *   - retention: number of seconds to keep run records (default: 1 week)
   *
   * @param array $options
   */
  public function __construct(array $options = [])
  {
    $dbPath = isset($options['db']) ? $options['db'] : './jobs.sqlite';
    $this->retention = isset($options['retention']) ? $options['retention'] : (60 * 60 * 24 * 7);

    try {
      $this->db = new PDO('sqlite:' . $dbPath);
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
      die("Could not connect to database: " . $e->getMessage());
    }

    // Create the runs table if it doesn't exist.
    $this->db->exec("CREATE TABLE IF NOT EXISTS runs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_id TEXT NOT NULL,
            scheduled_at TEXT NOT NULL,
            executed_at TEXT,
            status TEXT NOT NULL,
            output TEXT
        )");
  }

  /**
   * Register a job.
   *
   * @param string|false $interval A string like '30s', '1m', '1h', etc. or false.
   *                                When false, the job will run only when runJob() is called.
   * @param string       $jobId    A unique identifier for the job.
   * @param callable     $callback The callback to execute.
   */
  public function schedule($interval, string $jobId, callable $callback)
  {
    if ($interval !== false) {
      $seconds = $this->parseInterval($interval);
    } else {
      $seconds = false;
    }
    // For simplicity, each job id is unique. If re-scheduled the previous is overwritten.
    $this->jobs[$jobId] = [
      'job_id'   => $jobId,
      'interval' => $seconds,
      'callback' => $callback,
    ];
  }

  /**
   * Set a callback to be called when a job fails (after 3 failed attempts).
   *
   * @param callable $callback Receives the job id and the output from the final attempt.
   */
  public function onFailure(callable $callback)
  {
    $this->failureCallback = $callback;
  }

  /**
   * Run all scheduled jobs (only those with a valid interval).
   * Typically triggered by a cron job.
   */
  public function run()
  {
    foreach ($this->jobs as $job) {
      // Only run jobs that are set to run on a schedule (interval not false).
      if ($job['interval'] === false) {
        continue;
      }
      // Do not run if a job is already running.
      if ($this->isJobRunning($job['job_id'])) {
        continue;
      }
      // Check last run time from the database.
      $lastRun = $this->getLastRun($job['job_id']);
      $due = false;
      if (!$lastRun) {
        // If never run before, run immediately.
        $due = true;
      } else {
        $lastTime = strtotime($lastRun['scheduled_at']);
        if (time() >= $lastTime + $job['interval']) {
          $due = true;
        }
      }
      if ($due) {
        $this->executeJob($job);
      }
    }

    // Clean up old runs based on the retention period.
    $cutoff = date('Y-m-d H:i:s', time() - $this->retention);
    $stmt = $this->db->prepare("DELETE FROM runs WHERE scheduled_at < ?");
    $stmt->execute([$cutoff]);
  }

  /**
   * Run a job manually by its id.
   * Useful for testing or debugging.
   *
   * @param string $jobId
   */
  public function runJob(string $jobId)
  {
    if (isset($this->jobs[$jobId]) && !$this->isJobRunning($jobId)) {
      $this->executeJob($this->jobs[$jobId]);
    }
  }

  /**
   * Clear all registered jobs.
   */
  public function clear()
  {
    $this->jobs = [];
  }

  /**
   * Execute a job with up to 3 attempts.
   *
   * @param array $job
   */
  private function executeJob(array $job)
  {
    $jobId = $job['job_id'];
    $lastOutput = '';
    // Retry loop: up to 3 attempts.
    for ($attempt = 1; $attempt <= 3; $attempt++) {
      $scheduledAt = date('Y-m-d H:i:s');
      // Record the job run with initial status "running".
      $stmt = $this->db->prepare("INSERT INTO runs (job_id, scheduled_at, status, output) VALUES (?, ?, ?, ?)");
      $stmt->execute([$jobId, $scheduledAt, 'running', '']);
      $runId = $this->db->lastInsertId();

      // Capture output.
      ob_start();
      try {
        // Execute the job callback.
        call_user_func($job['callback']);
        $output = ob_get_clean();
        $executedAt = date('Y-m-d H:i:s');
        // Mark the run as successful.
        $stmt = $this->db->prepare("UPDATE runs SET executed_at = ?, status = ?, output = ? WHERE id = ?");
        $stmt->execute([$executedAt, 'success', $output, $runId]);
        return; // Job succeeded; exit.
      } catch (\Throwable $e) {
        $output = ob_get_clean();
        $output .= "\nException: " . $e->getMessage();
        $executedAt = date('Y-m-d H:i:s');
        // Mark the run as failed.
        $stmt = $this->db->prepare("UPDATE runs SET executed_at = ?, status = ?, output = ? WHERE id = ?");
        $stmt->execute([$executedAt, 'failed', $output, $runId]);
        $lastOutput = $output;
        // If not yet reached 3 attempts, the loop will retry immediately.
      }
    }
    // If we reach here, the job has failed 3 times.
    if ($this->failureCallback) {
      call_user_func($this->failureCallback, $jobId, $lastOutput);
    }
  }

  /**
   * Check if a job is already running.
   * A job is considered running if there is a run record with executed_at IS NULL.
   *
   * @param string $jobId
   * @return bool
   */
  private function isJobRunning(string $jobId): bool
  {
    $stmt = $this->db->prepare("SELECT COUNT(*) FROM runs WHERE job_id = ? AND executed_at IS NULL");
    $stmt->execute([$jobId]);
    $count = $stmt->fetchColumn();
    return $count > 0;
  }

  /**
   * Get the most recent run record for a given job.
   *
   * @param string $jobId
   * @return array|null
   */
  private function getLastRun(string $jobId)
  {
    $stmt = $this->db->prepare("SELECT * FROM runs WHERE job_id = ? ORDER BY scheduled_at DESC LIMIT 1");
    $stmt->execute([$jobId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result : null;
  }

  /**
   * Convert a human‐readable interval string to seconds.
   *
   * Supported formats: 
   *   - "30s" for 30 seconds
   *   - "1m"  for 1 minute
   *   - "1h"  for 1 hour
   *   - "1d"  for 1 day
   *   - "1w"  for 1 week
   *
   * @param string $interval
   * @return int
   */
  private function parseInterval(string $interval): int
  {
    if (is_numeric($interval)) {
      return (int)$interval;
    }

    if (preg_match('/^(\d+)([smhdw])$/i', $interval, $matches)) {
      $value = (int)$matches[1];
      $unit  = strtolower($matches[2]);
      switch ($unit) {
        case 's':
          return $value;
        case 'm':
          return $value * 60;
        case 'h':
          return $value * 3600;
        case 'd':
          return $value * 86400;
        case 'w':
          return $value * 604800;
      }
    }
    // Fallback: if parsing fails, treat as 0 seconds.
    return 0;
  }
}
