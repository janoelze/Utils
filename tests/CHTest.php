<?php

use PHPUnit\Framework\TestCase;
use JanOelze\Utils\CH;

class CHTest extends TestCase
{
  /**
   * @var string
   */
  private $cacheDir;

  protected function setUp(): void
  {
    // Create a unique temporary directory for testing
    $this->cacheDir = sys_get_temp_dir() . '/ch_test_' . uniqid();
    if (!mkdir($this->cacheDir, 0777, true) && !is_dir($this->cacheDir)) {
      throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->cacheDir));
    }
  }

  protected function tearDown(): void
  {
    $this->deleteDir($this->cacheDir);
  }

  /**
   * Recursively delete a directory.
   *
   * @param string $dir
   */
  private function deleteDir(string $dir): void
  {
    if (!is_dir($dir)) {
      return;
    }

    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
      if ($file->isDir()) {
        rmdir($file->getRealPath());
      } else {
        unlink($file->getRealPath());
      }
    }

    rmdir($dir);
  }

  public function testSetAndGetNumericExpiration(): void
  {
    $cache = new CH(['dir' => $this->cacheDir]);
    $cache->set('username', 'Jan', 600);
    $this->assertEquals('Jan', $cache->get('username'));
  }

  public function testSetAndGetHumanReadableExpiration(): void
  {
    $cache = new CH(['dir' => $this->cacheDir]);
    $cache->set('username', 'Jan', '1d');
    $this->assertEquals('Jan', $cache->get('username'));
  }

  public function testSetAndGetArray(): void
  {
    $cache = new CH(['dir' => $this->cacheDir]);
    $value = ['name' => 'Jan', 'age' => 30];
    $cache->set('user', $value, '1d');

    $retrieved = $cache->get('user');
    $this->assertIsArray($retrieved);
    $this->assertEquals('Jan', $retrieved['name']);
    $this->assertEquals(30, $retrieved['age']);
  }

  public function testExpiredValue(): void
  {
    $cache = new CH(['dir' => $this->cacheDir]);
    // Set expiration to 1 second
    $cache->set('temp', 'data', 1);
    sleep(2);
    $this->assertNull($cache->get('temp'));
  }

  public function testClearSpecificKey(): void
  {
    $cache = new CH(['dir' => $this->cacheDir]);
    $cache->set('key1', 'value1', 600);
    $cache->set('key2', 'value2', 600);

    // Clear only key1
    $cache->clear('key1');

    $this->assertNull($cache->get('key1'));
    $this->assertEquals('value2', $cache->get('key2'));
  }

  public function testClearAll(): void
  {
    $cache = new CH(['dir' => $this->cacheDir]);
    $cache->set('key1', 'value1', 600);
    $cache->set('key2', 'value2', 600);

    // Clear all keys
    $cache->clear();

    $this->assertNull($cache->get('key1'));
    $this->assertNull($cache->get('key2'));
  }

  public function testInvalidExpiration(): void
  {
    $cache = new CH(['dir' => $this->cacheDir]);

    $this->expectException(\InvalidArgumentException::class);
    $cache->set('invalid', 'data', 'abc');
  }
}
