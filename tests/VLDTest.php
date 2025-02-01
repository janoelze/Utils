<?php

use JanOelze\Utils\VLD;
use PHPUnit\Framework\TestCase;

class VLDTest extends TestCase
{
  protected $vld;

  protected function setUp(): void
  {
    $this->vld = new VLD();
  }

  public function testEmailValidation()
  {
    $this->assertTrue($this->vld->isValid('email', 'test@example.com'));
    $this->assertFalse($this->vld->isValid('email', 'invalid-email'));
  }

  public function testUrlValidation()
  {
    $this->assertTrue($this->vld->isValid('url', 'https://example.com'));
    $this->assertFalse($this->vld->isValid('url', 'invalid-url'));
  }

  public function testIpValidation()
  {
    $this->assertTrue($this->vld->isValid('ip', '192.168.0.1'));
    $this->assertFalse($this->vld->isValid('ip', 'invalid-ip'));
  }

  public function testAlphaValidation()
  {
    $this->assertTrue($this->vld->isValid('alpha', 'abc'));
    $this->assertFalse($this->vld->isValid('alpha', 'abc123'));
  }

  public function testAlphaNumericValidation()
  {
    $this->assertTrue($this->vld->isValid('alphaNumeric', 'abc123'));
    $this->assertFalse($this->vld->isValid('alphaNumeric', 'abc123!'));
  }

  public function testNumericValidation()
  {
    $this->assertTrue($this->vld->isValid('numeric', '123'));
    $this->assertFalse($this->vld->isValid('numeric', 'abc'));
  }

  public function testCustomRule()
  {
    $this->vld->addRule('custom', function ($value) {
      return $value === 'custom';
    });
    $this->assertTrue($this->vld->isValid('custom', 'custom'));
    $this->assertFalse($this->vld->isValid('custom', 'not-custom'));
  }

  public function testUndefinedRule()
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->vld->isValid('undefined', 'value');
  }
}
