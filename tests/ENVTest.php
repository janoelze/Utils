<?php

use PHPUnit\Framework\TestCase;
use JanOelze\Utils\ENV;

class ENVTest extends TestCase
{
  /**
   * @var string Path to the temporary .env file.
   */
  private string $tempEnvFile;

  protected function setUp(): void
  {
    // Create a temporary .env file with some sample data.
    $this->tempEnvFile = tempnam(sys_get_temp_dir(), 'env');
    $envContent = <<<EOD
# Sample environment file
APP_NAME="TestApp"
APP_ENV=testing
EOD;
    file_put_contents($this->tempEnvFile, $envContent);
  }

  protected function tearDown(): void
  {
    // Remove the temporary file.
    if (file_exists($this->tempEnvFile)) {
      unlink($this->tempEnvFile);
    }
  }

  public function testLoadEnvFile(): void
  {
    $env = new ENV($this->tempEnvFile);

    // Verify the environment variables loaded from the file.
    $this->assertEquals('TestApp', $env->get('APP_NAME'));
    $this->assertEquals('testing', $env->get('APP_ENV'));
  }

  public function testSetAndGetVariable(): void
  {
    $env = new ENV($this->tempEnvFile);
    $env->set('API_KEY', 'key-123456');

    // Test that the newly set variable is returned correctly.
    $this->assertEquals('key-123456', $env->get('API_KEY'));
  }

  public function testGetAllVariables(): void
  {
    $env = new ENV($this->tempEnvFile);
    $variables = $env->get();

    // Check that the returned array contains the variables from the .env file.
    $this->assertIsArray($variables);
    $this->assertArrayHasKey('APP_NAME', $variables);
    $this->assertArrayHasKey('APP_ENV', $variables);
  }
}
