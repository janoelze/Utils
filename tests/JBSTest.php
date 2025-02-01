<?php

use JanOelze\Utils\JBS;
use PHPUnit\Framework\TestCase;

class JBSTest extends TestCase
{
  /**
   * Test that a manually run job (with interval set to false) is executed.
   */
  public function testManualRunExecutesJob()
  {
    $flag = false;
    $jbs = new JBS(['db' => ':memory:']);
    $jbs->schedule(false, 'manualJob', function () use (&$flag) {
      $flag = true;
    });
    $jbs->runJob('manualJob');
    $this->assertTrue($flag, 'The manual job should have set the flag to true.');
  }

  /**
   * Test that a recurring job is executed immediately if it has never run before.
   */
  public function testRecurringJobRunsImmediately()
  {
    $flag = false;
    $jbs = new JBS(['db' => ':memory:']);
    $jbs->schedule('1s', 'recurringJob', function () use (&$flag) {
      $flag = true;
    });
    // Since the job never ran before, run() should execute it immediately.
    $jbs->run();
    $this->assertTrue($flag, 'The recurring job should have run immediately when never run before.');
  }

  /**
   * Test that a failing job triggers the onFailure callback after 3 attempts.
   */
  public function testFailureCallbackIsCalled()
  {
    $failureCalled = false;
    $failureOutput = '';
    $jbs = new JBS(['db' => ':memory:']);
    $jbs->onFailure(function ($jobId, $output) use (&$failureCalled, &$failureOutput) {
      $failureCalled = true;
      $failureOutput = $output;
    });
    $jbs->schedule(false, 'failingJob', function () {
      throw new \Exception("Test Exception");
    });
    $jbs->runJob('failingJob');
    $this->assertTrue($failureCalled, 'The failure callback should have been called.');
    $this->assertStringContainsString("Test Exception", $failureOutput, 'The failure output should contain the exception message.');
  }

  /**
   * Test that clearing jobs prevents a job from being run.
   */
  public function testClearJobs()
  {
    $flag = false;
    $jbs = new JBS(['db' => ':memory:']);
    $jbs->schedule(false, 'jobToClear', function () use (&$flag) {
      $flag = true;
    });
    $jbs->clear();
    // After clearing, running the job should have no effect.
    $jbs->runJob('jobToClear');
    $this->assertFalse($flag, 'The job should not run after being cleared.');
  }
}
