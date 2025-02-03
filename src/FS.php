<?php

namespace JanOelze\Utils;

/**
 * FS provides a simple API for basic file system operations.
 *
 * This class supports listing files with glob patterns, copying files/directories,
 * creating directories recursively, removing files/directories, and basic read/write
 * operations on files.
 *
 * All methods follow camelCase naming conventions, and errors are handled via exceptions.
 */
class FS
{
  /**
   * List files based on a glob pattern.
   *
   * @param string $pattern The glob pattern to match files (e.g., '/tmp/*.txt').
   * @return array Returns an array of file paths that match the pattern.
   * @throws \RuntimeException If the glob operation fails.
   */
  public function listFiles(string $pattern): array
  {
    $files = glob($pattern);
    if ($files === false) {
      throw new \RuntimeException("Failed to execute glob with pattern: $pattern");
    }
    return $files;
  }

  /**
   * Copy a file or directory.
   *
   * For directories, the operation is recursive.
   *
   * @param string $source The source file or directory path.
   * @param string $destination The destination path.
   * @return bool Returns true on success.
   * @throws \RuntimeException If the copy operation fails.
   */
  public function copy(string $source, string $destination): bool
  {
    if (is_dir($source)) {
      // Recursively copy directories
      $this->recursiveCopy($source, $destination);
      return true;
    } elseif (is_file($source)) {
      if (!copy($source, $destination)) {
        throw new \RuntimeException("Failed to copy file from $source to $destination");
      }
      return true;
    }
    throw new \RuntimeException("Source $source is neither a file nor a directory");
  }

  /**
   * Recursively copy a directory.
   *
   * @param string $source The source directory.
   * @param string $destination The destination directory.
   * @return void
   * @throws \RuntimeException If copying fails.
   */
  protected function recursiveCopy(string $source, string $destination): void
  {
    if (!is_dir($destination) && !mkdir($destination, 0777, true) && !is_dir($destination)) {
      throw new \RuntimeException("Failed to create directory: $destination");
    }
    $dir = opendir($source);
    if (!$dir) {
      throw new \RuntimeException("Failed to open directory: $source");
    }
    while (($file = readdir($dir)) !== false) {
      if ($file === '.' || $file === '..') {
        continue;
      }
      $srcPath  = $source . DIRECTORY_SEPARATOR . $file;
      $destPath = $destination . DIRECTORY_SEPARATOR . $file;
      if (is_dir($srcPath)) {
        $this->recursiveCopy($srcPath, $destPath);
      } else {
        if (!copy($srcPath, $destPath)) {
          throw new \RuntimeException("Failed to copy file from $srcPath to $destPath");
        }
      }
    }
    closedir($dir);
  }

  /**
   * Create a directory recursively with specified permissions.
   *
   * @param string $directory The directory path to create.
   * @param int $permissions The directory permissions (default is 0777).
   * @return bool Returns true on success.
   * @throws \RuntimeException If directory creation fails.
   */
  public function createDirectory(string $directory, int $permissions = 0777): bool
  {
    if (!is_dir($directory) && !mkdir($directory, $permissions, true) && !is_dir($directory)) {
      throw new \RuntimeException("Failed to create directory: $directory");
    }
    return true;
  }

  /**
   * Remove a file or directory.
   *
   * For directories, the removal is recursive.
   *
   * @param string $path The file or directory path to remove.
   * @return bool Returns true on success.
   * @throws \RuntimeException If removal fails.
   */
  public function remove(string $path): bool
  {
    if (is_file($path)) {
      if (!unlink($path)) {
        throw new \RuntimeException("Failed to remove file: $path");
      }
    } elseif (is_dir($path)) {
      $this->recursiveRemove($path);
    } else {
      throw new \RuntimeException("Path $path does not exist");
    }
    return true;
  }

  /**
   * Recursively remove a directory.
   *
   * @param string $directory The directory to remove.
   * @return void
   * @throws \RuntimeException If removal fails.
   */
  protected function recursiveRemove(string $directory): void
  {
    $items = new \FilesystemIterator($directory);
    foreach ($items as $item) {
      if ($item->isDir() && !$item->isLink()) {
        $this->recursiveRemove($item->getPathname());
      } else {
        if (!unlink($item->getPathname())) {
          throw new \RuntimeException("Failed to remove file: " . $item->getPathname());
        }
      }
    }
    if (!rmdir($directory)) {
      throw new \RuntimeException("Failed to remove directory: $directory");
    }
  }

  /**
   * Write content to a file (overwrites existing content).
   *
   * @param string $path The file path.
   * @param string $content The content to write.
   * @return bool Returns true on success.
   * @throws \RuntimeException If writing fails.
   */
  public function write(string $path, string $content): bool
  {
    if (file_put_contents($path, $content) === false) {
      throw new \RuntimeException("Failed to write to file: $path");
    }
    return true;
  }

  /**
   * Append content to a file.
   *
   * @param string $path The file path.
   * @param string $content The content to append.
   * @return bool Returns true on success.
   * @throws \RuntimeException If appending fails.
   */
  public function append(string $path, string $content): bool
  {
    if (file_put_contents($path, $content, FILE_APPEND) === false) {
      throw new \RuntimeException("Failed to append to file: $path");
    }
    return true;
  }

  /**
   * Read and return the contents of a file.
   *
   * @param string $path The file path.
   * @return string The file content.
   * @throws \RuntimeException If reading fails.
   */
  public function read(string $path): string
  {
    $content = file_get_contents($path);
    if ($content === false) {
      throw new \RuntimeException("Failed to read file: $path");
    }
    return $content;
  }

  /**
   * Zip a directory into a zip file.
   *
   * @param string $source The source directory to zip.
   * @param string $destination The destination zip file path.
   * @return bool Returns true on success.
   * @throws \RuntimeException If zipping fails.
   */
  public function zip(string $source, string $destination): bool
  {
    if (!is_dir($source)) {
      throw new \RuntimeException("Source $source is not a directory");
    }
    $zip = new \ZipArchive();
    if ($zip->open($destination, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
      throw new \RuntimeException("Could not open <$destination> for zipping");
    }
    $source = realpath($source);
    $files = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($files as $file) {
      $filePath = $file->getRealPath();
      $relativePath = substr($filePath, strlen($source) + 1);
      if ($file->isDir()) {
        $zip->addEmptyDir($relativePath);
      } else {
        if (!$zip->addFile($filePath, $relativePath)) {
          throw new \RuntimeException("Could not add file: $filePath");
        }
      }
    }
    $zip->close();
    return true;
  }
}