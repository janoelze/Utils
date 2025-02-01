<?php

namespace JanOelze\Utils;

class ENV
{
  /**
   * @var array The loaded environment variables.
   */
  protected $variables = [];

  /**
   * Create a new instance of ENV.
   *
   * Reads global environment variables and overrides them with the values
   * found in the given .env file, if available.
   *
   * @param ?string $envFilePath The path to the .env file.
   */
  public function __construct(?string $envFilePath = null)
  {
    // Start with global environment variables.
    // $_SERVER and $_ENV are merged so that values in $_ENV (if any) override $_SERVER.
    $this->variables = array_merge($_SERVER, $_ENV);

    // If a .env file is provided and exists, load its values.
    if ($envFilePath !== null && file_exists($envFilePath)) {
      $this->loadEnvFile($envFilePath);
    }
  }

  /**
   * Loads and parses a .env file, merging its variables into the current environment.
   *
   * @param string $filePath The path to the .env file.
   */
  protected function loadEnvFile(string $filePath): void
  {
    // Read file lines, ignoring empty lines.
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
      $line = trim($line);

      // Skip comments (lines starting with '#' or ';') and blank lines.
      if (empty($line) || $line[0] === '#' || $line[0] === ';') {
        continue;
      }

      // Remove "export" if present.
      if (strpos($line, 'export ') === 0) {
        $line = substr($line, 7);
      }

      // Only process lines containing an '='.
      if (strpos($line, '=') === false) {
        continue;
      }

      // Split into key and value at the first '='.
      list($key, $value) = explode('=', $line, 2);
      $key   = trim($key);
      $value = trim($value);

      // Remove surrounding quotes from the value, if present.
      if (
        (substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
        (substr($value, 0, 1) === "'" && substr($value, -1) === "'")
      ) {
        $value = substr($value, 1, -1);
      }

      // Store the variable (overriding global values if necessary).
      $this->variables[$key] = $value;

      // Also update the PHP environment.
      putenv("$key=$value");
    }
  }

  /**
   * Retrieves an environment variable or all variables.
   *
   * If a key is provided, returns the associated value (or null if not found).
   * If no key is provided, returns the complete array of environment variables.
   *
   * @param ?string $key The name of the environment variable.
   *
   * @return mixed The value of the environment variable, or the full array.
   */
  public function get(?string $key = null)
  {
    if ($key === null) {
      return $this->variables;
    }

    return $this->variables[$key] ?? null;
  }

  /**
   * Sets an environment variable.
   *
   * This updates the internal storage and also calls putenv() to update the global state.
   *
   * @param string $key   The name of the environment variable.
   * @param mixed  $value The value to set.
   */
  public function set(string $key, $value): void
  {
    $this->variables[$key] = $value;
    putenv("$key=$value");
  }
}
