<?php

namespace JanOelze\Utils;

class CH
{
  protected $dir;

  /**
   * Constructor.
   *
   * @param array $config Array with configuration options. Must include 'dir' for the cache directory.
   *
   * @throws \InvalidArgumentException if the 'dir' option is missing.
   * @throws \RuntimeException if the cache directory cannot be created.
   */
  public function __construct(array $config = [])
  {
    if (!isset($config['dir'])) {
      throw new \InvalidArgumentException("Cache directory 'dir' is required.");
    }

    $this->dir = rtrim($config['dir'], DIRECTORY_SEPARATOR);

    if (!is_dir($this->dir)) {
      if (!mkdir($this->dir, 0777, true)) {
        throw new \RuntimeException("Failed to create cache directory: {$this->dir}");
      }
    }
  }

  /**
   * Stores a value in the cache.
   *
   * @param string       $key        The cache key.
   * @param mixed        $value      The value to cache. Can be of any type.
   * @param int|string   $expiration Expiration time in seconds (numeric) or as a human-readable string (e.g., "1d" for 1 day).
   *
   * @throws \InvalidArgumentException if the expiration format is invalid.
   */
  public function set($key, $value, $expiration)
  {
    $expireTime = $this->parseExpiration($expiration);
    $data = [
      'expire' => $expireTime,
      'value'  => $value,
    ];

    $file = $this->getFilePath($key);
    file_put_contents($file, serialize($data));
  }

  /**
   * Retrieves a cached value.
   *
   * @param string $key The cache key.
   *
   * @return mixed|null The cached value or null if not found or expired.
   */
  public function get($key)
  {
    $file = $this->getFilePath($key);

    if (!file_exists($file)) {
      return null;
    }

    $data = file_get_contents($file);
    if ($data === false) {
      return null;
    }

    $data = unserialize($data);
    if (!is_array($data) || !isset($data['expire']) || !array_key_exists('value', $data)) {
      return null;
    }

    // Check if the cache has expired.
    if (time() > $data['expire']) {
      // Cache expired; remove the file.
      unlink($file);
      return null;
    }

    return $data['value'];
  }

  /**
   * Clears cached entries.
   *
   * @param string|null $key If provided, only the specific cache entry is cleared.
   *                         Otherwise, all cache files are removed.
   */
  public function clear($key = null)
  {
    if ($key !== null) {
      $file = $this->getFilePath($key);
      if (file_exists($file)) {
        unlink($file);
      }
    } else {
      // Clear all cache files.
      foreach (glob($this->dir . DIRECTORY_SEPARATOR . '*.cache') as $file) {
        if (is_file($file)) {
          unlink($file);
        }
      }
    }
  }

  /**
   * Generates a file path for a given cache key.
   *
   * @param string $key The cache key.
   *
   * @return string The full path to the cache file.
   */
  protected function getFilePath($key)
  {
    return $this->dir . DIRECTORY_SEPARATOR . md5($key) . '.cache';
  }

  /**
   * Parses the expiration parameter.
   *
   * @param int|string $expiration Expiration time in seconds or as a human-readable string.
   *
   * @return int The Unix timestamp when the cache should expire.
   *
   * @throws \InvalidArgumentException if the expiration format is invalid.
   */
  protected function parseExpiration($expiration)
  {
    if (is_numeric($expiration)) {
      return time() + (int)$expiration;
    } elseif (is_string($expiration)) {
      // Supports formats like "10s", "5m", "2h", "1d"
      if (preg_match('/^(\d+)\s*([smhd])$/i', $expiration, $matches)) {
        $num  = (int)$matches[1];
        $unit = strtolower($matches[2]);

        switch ($unit) {
          case 's':
            $seconds = $num;
            break;
          case 'm':
            $seconds = $num * 60;
            break;
          case 'h':
            $seconds = $num * 3600;
            break;
          case 'd':
            $seconds = $num * 86400;
            break;
          default:
            throw new \InvalidArgumentException("Invalid time unit in expiration: $expiration");
        }

        return time() + $seconds;
      } else {
        throw new \InvalidArgumentException("Invalid expiration format: $expiration");
      }
    }

    throw new \InvalidArgumentException("Expiration must be a numeric value or a valid time string.");
  }
}
