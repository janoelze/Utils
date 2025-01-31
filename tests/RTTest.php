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

  public function testSendJson()
  {
    $rt = new RT();
    $data = ['success' => true];
    
    // Capture output
    ob_start();
    $rt->sendJson($data);
    $output = ob_get_clean();
    
    $this->assertJson($output);
    $this->assertEquals(json_encode($data), $output);
  }

  public function testSetHeader()
  {
    $rt = new RT();
    $rt->setHeader('X-Custom-Header', 'Value');
    
    // Since headers cannot be tested directly, ensure no exception is thrown
    $this->assertTrue(true);
  }

  public function testGetCurrentPage()
  {
    $_GET['page'] = 'home';
    $rt = new RT(['default_page' => 'home']);
    $rt->addPage('GET', 'home', function () {
      return ['title' => 'Home'];
    });
    
    $rt->run();
    $this->assertEquals('home', $rt->getCurrentPage());
  }

  public function testRedirect()
  {
    $rt = new RT();
    
    // Since headers cannot be tested directly, ensure no exception is thrown
    $this->expectOutputString('');
    $rt->redirect('http://example.com');
  }
}
