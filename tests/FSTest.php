<?php

use PHPUnit\Framework\TestCase;
use JanOelze\Utils\FS;

class FSTest extends TestCase
{
  /** @var FS */
  protected $fs;

  /** @var string */
  protected $testFile;

  /** @var string */
  protected $testDir;

  protected function setUp(): void
  {
    $this->fs = new FS();
    // Use the system temporary directory for tests.
    $this->testFile = sys_get_temp_dir() . '/test_hello.txt';
    $this->testDir  = sys_get_temp_dir() . '/test_dir';
  }

  protected function tearDown(): void
  {
    // Cleanup the test file if it exists.
    if (file_exists($this->testFile)) {
      unlink($this->testFile);
    }
    // Cleanup the test directory recursively if it exists.
    if (is_dir($this->testDir)) {
      $this->deleteDirectory($this->testDir);
    }
  }

  /**
   * Recursively delete a directory.
   *
   * @param string $dir
   */
  protected function deleteDirectory(string $dir): void
  {
    if (!is_dir($dir)) {
      return;
    }
    $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileinfo) {
      if ($fileinfo->isDir()) {
        rmdir($fileinfo->getRealPath());
      } else {
        unlink($fileinfo->getRealPath());
      }
    }
    rmdir($dir);
  }

  public function testWriteAndReadFile(): void
  {
    $content = 'Hello, PHPUnit!';
    // Write content to a file.
    $this->assertTrue($this->fs->write($this->testFile, $content));
    $this->assertFileExists($this->testFile);

    // Read the content back.
    $readContent = $this->fs->read($this->testFile);
    $this->assertEquals($content, $readContent);
  }

  public function testAppendToFile(): void
  {
    $initialContent = 'Hello';
    $appendContent  = ', PHPUnit!';
    $this->fs->write($this->testFile, $initialContent);
    $this->fs->append($this->testFile, $appendContent);

    $expected = $initialContent . $appendContent;
    $this->assertEquals($expected, $this->fs->read($this->testFile));
  }

  public function testCreateAndRemoveDirectory(): void
  {
    // Create a new directory.
    $this->assertTrue($this->fs->createDirectory($this->testDir));
    $this->assertDirectoryExists($this->testDir);

    // Create a file inside the directory.
    $filePath = $this->testDir . '/file.txt';
    $this->fs->write($filePath, 'Test file');
    $this->assertFileExists($filePath);

    // Remove the directory recursively.
    $this->assertTrue($this->fs->remove($this->testDir));
    $this->assertDirectoryDoesNotExist($this->testDir);
  }

  public function testCopyFile(): void
  {
    $source      = $this->testFile;
    $destination = sys_get_temp_dir() . '/test_copy.txt';

    $content = 'Copy me!';
    $this->fs->write($source, $content);
    $this->assertFileExists($source);

    // Copy the source file to the destination.
    $this->assertTrue($this->fs->copy($source, $destination));
    $this->assertFileExists($destination);
    $this->assertEquals($content, $this->fs->read($destination));

    // Clean up the copied file.
    unlink($destination);
  }

  public function testZipDirectory(): void
  {
    // Create a test directory with a file
    $zipDir = sys_get_temp_dir() . '/test_zip_dir';
    $zipFile = sys_get_temp_dir() . '/test_archive.zip';
    if (!is_dir($zipDir)) {
      mkdir($zipDir, 0777, true);
    }
    file_put_contents($zipDir . '/file.txt', 'Zip content');

    // Zip the directory
    $this->assertTrue($this->fs->zip($zipDir, $zipFile));
    $this->assertFileExists($zipFile);

    // Verify the zip file contains the file
    $zip = new \ZipArchive();
    $res = $zip->open($zipFile);
    $this->assertTrue($res === true);
    $this->assertNotFalse($zip->locateName('file.txt'));
    $zip->close();

    // Cleanup
    unlink($zipFile);
    $this->deleteDirectory($zipDir);
  }

  public function testInfo(): void
  {
    // Test info() for a file
    $content = 'Test content';
    $this->fs->write($this->testFile, $content);
    $info = $this->fs->info($this->testFile);
    $this->assertEquals('file', $info['type']);
    $this->assertArrayHasKey('extension', $info);
    $this->assertEquals(basename($this->testFile), $info['basename']);

    // Test info() for a directory
    $this->fs->createDirectory($this->testDir);
    $dirInfo = $this->fs->info($this->testDir);
    $this->assertEquals('dir', $dirInfo['type']);
    $this->assertArrayHasKey('realpath', $dirInfo);
    $this->assertEquals(basename($this->testDir), $dirInfo['basename']);
  }
}