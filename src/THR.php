<?php

namespace JanOelze\Utils;

/**
 * A simple THR implementation with IPC using pcntl_fork and socket pairs.
 * Each task receives input data and returns a result.
 *
 * Note: This requires the pcntl and sockets extensions (enabled in CLI PHP).
 */
class THR
{
  protected int $maxWorkers;
  protected int $currentWorkers = 0;
  // Store parent's end of the socket for each child pid.
  protected array $childSockets = [];
  // Accumulate results from finished child tasks.
  protected array $results = [];

  /**
   * Constructor.
   *
   * @param array $config Configuration options. Accepts:
   *                      - max_workers (int): Maximum number of concurrent workers.
   */
  public function __construct(array $config = [])
  {
    if (!function_exists('pcntl_fork') || !function_exists('stream_socket_pair')) {
      throw new \RuntimeException('Required functions (pcntl_fork, stream_socket_pair) are not available.');
    }
    // Note: Using snake_case as per your config.
    $this->maxWorkers = $config['max_workers'] ?? 4;
  }

  /**
   * Submits a task to the pool.
   *
   * @param callable $task A callable that takes one parameter (input data) and returns a result.
   * @param mixed    $input Data to feed into the task.
   */
  public function submit(callable $task, $input = null): void
  {
    // Wait until a worker slot is free.
    while ($this->currentWorkers >= $this->maxWorkers) {
      $this->collectChildResult();
    }

    // Create a socket pair for IPC between parent and child.
    $sockets = stream_socket_pair(AF_UNIX, SOCK_STREAM, 0);
    if ($sockets === false) {
      throw new \RuntimeException('Failed to create socket pair.');
    }
    list($parentSocket, $childSocket) = $sockets;

    $pid = pcntl_fork();
    if ($pid == -1) {
      throw new \RuntimeException('Could not fork process.');
    } elseif ($pid) {
      // Parent process:
      // Close the child's socket end.
      fclose($childSocket);
      // Store the parent's socket so we can read later.
      $this->childSockets[$pid] = $parentSocket;
      $this->currentWorkers++;
    } else {
      // Child process:
      // Close the parent's socket end.
      fclose($parentSocket);
      // Execute the task.
      $result = $task($input);
      // Write the result as JSON to the socket.
      $data = json_encode($result);
      fwrite($childSocket, $data);
      fclose($childSocket);
      // Exit the child process.
      exit(0);
    }
  }

  /**
   * Collects the result from one finished child process.
   */
  protected function collectChildResult(): void
  {
    // Wait for any child process to exit.
    $exitedPid = pcntl_wait($status);
    if ($exitedPid > 0 && isset($this->childSockets[$exitedPid])) {
      $socket = $this->childSockets[$exitedPid];
      // Read the full content from the socket.
      $data = stream_get_contents($socket);
      fclose($socket);
      unset($this->childSockets[$exitedPid]);
      $this->currentWorkers--;
      // Decode the JSON data and store the result.
      $this->results[$exitedPid] = json_decode($data, true);
    }
  }

  /**
   * Waits for all submitted tasks to finish and returns an array of results.
   *
   * @return array An associative array where keys are child pids and values are task results.
   */
  public function wait(): array
  {
    while ($this->currentWorkers > 0) {
      $this->collectChildResult();
    }
    return $this->results;
  }
}