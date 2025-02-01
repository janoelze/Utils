<?php

use PHPUnit\Framework\TestCase;
use JanOelze\Utils\LG;

class LGTest extends TestCase
{
  /**
   * Test logging to the console.
   */
  public function testConsoleLogging()
  {
    // Start output buffering to capture console output.
    ob_start();

    // Create an LG instance with console destination and disabled colors.
    $lg = new LG([
      'date_format'  => 'd-m-Y H:i:s',
      'colors'       => false,
      'destinations' => ['console']
    ]);

    // Log a test message.
    $lg->log('Test console message');

    // Get the captured output.
    $output = trim(ob_get_clean());

    // Assert that the output matches the expected format.
    // Expected format: "DD-MM-YYYY HH:MM:SS [LOG] Test console message"
    $this->assertMatchesRegularExpression(
      '/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2} \[LOG\] Test console message$/',
      $output
    );
  }

  /**
   * Test logging to a file.
   */
  public function testFileLogging()
  {
    // Create a temporary file to log to.
    $tempFile = tempnam(sys_get_temp_dir(), 'LGTest_');

    // Create an LG instance with the temporary file as a destination.
    $lg = new LG([
      'date_format'  => 'd-m-Y H:i:s',
      'colors'       => false,
      'destinations' => [$tempFile]
    ]);

    // Log a test message.
    $lg->log('Test file message');

    // Read the file contents.
    $fileContent = trim(file_get_contents($tempFile));

    // Clean up the temporary file.
    unlink($tempFile);

    // Assert that the file content matches the expected format.
    $this->assertMatchesRegularExpression(
      '/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2} \[LOG\] Test file message$/',
      $fileContent
    );
  }

  /**
   * Test that multiple arguments are concatenated correctly.
   */
  public function testMultipleArgumentsLogging()
  {
    ob_start();

    $lg = new LG([
      'date_format'  => 'd-m-Y H:i:s',
      'colors'       => false,
      'destinations' => ['console']
    ]);

    // Log a message with multiple arguments.
    $lg->log('Multiple', 'arguments', 123);
    $output = trim(ob_get_clean());

    // Expected output: "DD-MM-YYYY HH:MM:SS [LOG] Multiple arguments 123"
    $this->assertMatchesRegularExpression(
      '/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2} \[LOG\] Multiple arguments 123$/',
      $output
    );
  }

  /**
   * Test that arrays are pretty-printed as JSON.
   */
  public function testJsonLogging()
  {
    ob_start();

    $lg = new LG([
      'date_format'  => 'd-m-Y H:i:s',
      'colors'       => false,
      'destinations' => ['console']
    ]);

    $data = ['key' => 'value'];
    $lg->log($data);
    $output = ob_get_clean();

    // The output should include the JSON formatted version of $data.
    $this->assertStringContainsString('{', $output);
    $this->assertStringContainsString('"key": "value"', $output);
    $this->assertStringContainsString('}', $output);
  }
}
