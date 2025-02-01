<?php

namespace JanOelze\Utils;

class LG
{
  /**
   * @var string
   */
  private $dateFormat;

  /**
   * @var bool
   */
  private $colors;

  /**
   * @var array
   */
  private $destinations;

  /**
   * ANSI color codes for console output.
   *
   * @var array
   */
  private $levelColors = [
    'LOG' => "\033[0m",   // default (no extra color)
    'WRN' => "\033[33m",  // yellow
    'ERR' => "\033[31m",  // red
    'SCS' => "\033[32m",  // green
    'DBG' => "\033[34m",  // blue
  ];

  /**
   * ANSI reset code.
   *
   * @var string
   */
  private $resetColor = "\033[0m";

  /**
   * Constructor.
   *
   * Accepts an array with the following options:
   *  - date_format: The PHP date() format to use for the timestamp.
   *  - colors: Boolean to enable/disable ANSI colors for console output.
   *  - destinations: An array of destinations. Use "console" for console output,
   *                  and file paths for logging to files.
   *
   * @param array $config
   */
  public function __construct(array $config = [])
  {
    $this->dateFormat   = $config['date_format']   ?? 'Y-m-d H:i:s';
    $this->colors       = $config['colors']        ?? false;
    $this->destinations = $config['destinations']  ?? ['console'];
  }

  /**
   * Internal method to log a message with a given log level.
   *
   * @param string $level The log level (e.g., LOG, WRN, ERR, SCS, DBG).
   * @param array  $messages The parts of the message to be logged.
   */
  private function logMessage(string $level, array $messages): void
  {
    $timestamp = date($this->dateFormat);

    // Process each message part:
    // If the argument is an array, object, or any non-scalar,
    // attempt to pretty print it as JSON. If JSON encoding fails,
    // fall back to using print_r().
    $processedMessages = array_map(function ($msg) {
      if (!is_scalar($msg)) {
        $json = json_encode($msg, JSON_PRETTY_PRINT);
        if ($json !== false) {
          return $json;
        }
        return print_r($msg, true);
      }
      return (string)$msg;
    }, $messages);

    // Concatenate all parts with a space.
    $messageStr = implode(' ', $processedMessages);

    // Build the log line.
    $plainLogLine = sprintf("%s [%s] %s", $timestamp, $level, $messageStr);

    // Send the log line to each destination.
    foreach ($this->destinations as $destination) {
      if ($destination === 'console') {
        // For console output, use colors if enabled.
        if ($this->colors && isset($this->levelColors[$level])) {
          $coloredLogLine = $this->levelColors[$level] . $plainLogLine . $this->resetColor;
          echo $coloredLogLine . PHP_EOL;
        } else {
          echo $plainLogLine . PHP_EOL;
        }
      } else {
        // Assume any destination other than "console" is a file path.
        file_put_contents($destination, $plainLogLine . PHP_EOL, FILE_APPEND);
      }
    }
  }

  /**
   * Log a message with the "LOG" level.
   *
   * Accepts multiple arguments which are concatenated with spaces.
   *
   * @param mixed ...$messages
   */
  public function log(...$messages): void
  {
    $this->logMessage('LOG', $messages);
  }

  /**
   * Alias for log().
   *
   * @param mixed ...$messages
   */
  public function print(...$messages): void
  {
    $this->log(...$messages);
  }

  /**
   * Alias for log().
   *
   * @param mixed ...$messages
   */
  public function write(...$messages): void
  {
    $this->log(...$messages);
  }

  /**
   * Log a warning message (level "WRN").
   *
   * @param mixed ...$messages
   */
  public function warn(...$messages): void
  {
    $this->logMessage('WRN', $messages);
  }

  /**
   * Log an error message (level "ERR").
   *
   * @param mixed ...$messages
   */
  public function error(...$messages): void
  {
    $this->logMessage('ERR', $messages);
  }

  /**
   * Log a success message (level "SCS").
   *
   * @param mixed ...$messages
   */
  public function success(...$messages): void
  {
    $this->logMessage('SCS', $messages);
  }

  /**
   * Log a debug message (level "DBG").
   *
   * @param mixed ...$messages
   */
  public function debug(...$messages): void
  {
    $this->logMessage('DBG', $messages);
  }
}