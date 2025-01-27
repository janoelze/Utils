<?php

use JanOelze\Utils\RT;
use PHPUnit\Framework\TestCase;

class RTTest extends TestCase
{
  public function testGetReq()
  {
    // Simulate a GET request
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['page'] = 'home';

    $rt = new RT([
      'default_page' => 'home',
    ]);

    $rt->addPage('GET', 'home', function () {
      return [
        'title' => 'Hello, world!',
        'description' => 'This is a simple example of a route.',
      ];
    });

    $data = $rt->run();

    $this->assertEquals('Hello, world!', $data['title']);
    $this->assertEquals('This is a simple example of a route.', $data['description']);
  }
  public function testPostReq()
  {
    // Simulate a POST request
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET['page'] = 'api';
    $_POST['name'] = 'Alice';

    $rt = new RT([
      'default_page' => 'home',
    ]);

    $rt->addPage('POST', 'api', function ($req) {
      return [
        'user' => $_POST['name'],
      ];
    });

    $data = $rt->run();

    $this->assertEquals('Alice', $data['user']);
  }
}
