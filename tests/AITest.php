<?php
// tests/AITest.php

use PHPUnit\Framework\TestCase;
use JanOelze\Utils\AI;

class AITest extends TestCase
{
  /**
   * Test that the prompt builder correctly combines system and user messages.
   */
  public function testGenerateResponse()
  {
    $ai = new AI([
      'platform' => [
        'name' => 'openai',
        'api_key' => getenv('OPENAI_API_KEY') ?: 'your-api-key',
        'model' => 'gpt-4o-mini'
      ]
    ]);

    // Create a new prompt builder
    $prompt = $ai->prompt();
    $secret_code = rand(1000, 9999);

    // Add a system message line
    $prompt->addSystemMessage("Today's code is :code", ['code' => $secret_code]);
    $prompt->addSystemMessage("Your task is to answer user questions.");
    $prompt->addUserMessage("What is today's code?");

    // Generate the response
    $res = $ai->generate($prompt->get());

    // Ensure $res['response'] contains the secret code
    $this->assertStringContainsString((string)$secret_code, $res['response']);
  }
}
