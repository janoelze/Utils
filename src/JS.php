<?php

namespace JanOelze\Utils;

class JS
{
  private $filePath;

  public function __construct(string $filePath)
  {
    $this->filePath = $filePath;
    // Ensure the file exists
    if (!file_exists($this->filePath)) {
      file_put_contents($this->filePath, json_encode([]));
    }
  }

  /**
   * Read the file under a shared lock.
   */
  private function loadShared(): array
  {
    $handle = fopen($this->filePath, 'c+');
    if (!$handle) {
      throw new \Exception("Unable to open file for reading: {$this->filePath}");
    }
    if (!flock($handle, LOCK_SH)) {
      fclose($handle);
      throw new \Exception("Unable to acquire shared lock on file: {$this->filePath}");
    }
    // Read the current contents.
    $contents = stream_get_contents($handle);
    $data = json_decode($contents, true) ?? [];
    // Release the lock and close.
    flock($handle, LOCK_UN);
    fclose($handle);
    return $data;
  }

  /**
   * Read-modify-write under an exclusive lock.
   *
   * The callback receives the current data (an array) and must return
   * the updated data.
   */
  private function update(callable $callback): void
  {
    $handle = fopen($this->filePath, 'c+');
    if (!$handle) {
      throw new \Exception("Unable to open file for updating: {$this->filePath}");
    }
    if (!flock($handle, LOCK_EX)) {
      fclose($handle);
      throw new \Exception("Unable to acquire exclusive lock on file: {$this->filePath}");
    }

    // Read current contents.
    $contents = stream_get_contents($handle);
    $data = json_decode($contents, true) ?? [];

    // Invoke the callback to update the data.
    $data = $callback($data);

    // Truncate and rewind.
    ftruncate($handle, 0);
    rewind($handle);

    // Write new JSON data.
    $json = json_encode($data, JSON_PRETTY_PRINT);
    if (fwrite($handle, $json) === false) {
      flock($handle, LOCK_UN);
      fclose($handle);
      throw new \Exception("Error writing updated data to file: {$this->filePath}");
    }

    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
  }

  // Public interface methods

  /**
   * Get the entire JSON store.
   */
  public function getAll(): array
  {
    return $this->loadShared();
  }

  /**
   * Get all keys in the store.
   */
  public function getKeys(): array
  {
    return array_keys($this->loadShared());
  }

  /**
   * Get a specific key.
   */
  public function get(string $key)
  {
    $data = $this->loadShared();
    return $data[$key] ?? null;
  }

  /**
   * Set or update a key with a value.
   */
  public function set(string $key, $value): void
  {
    $this->update(function (array $data) use ($key, $value) {
      $data[$key] = $value;
      return $data;
    });
  }

  /**
   * Delete a key from the store.
   */
  public function delete(string $key): void
  {
    $this->update(function (array $data) use ($key) {
      if (isset($data[$key])) {
        unset($data[$key]);
      }
      return $data;
    });
  }

  /**
   * Clear the entire store.
   */
  public function clear(): void
  {
    $this->update(function (array $data) {
      return [];
    });
  }
}
