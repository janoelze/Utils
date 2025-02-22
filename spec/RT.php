<?php

require_once __DIR__ . '/../vendor/autoload.php';

use JanOelze\Utils\RT;

$rt = new RT([
  'default_page' => 'home',
]);

// Register a middleware that sets the Content-Type header based on the query string
$rt->addMiddleware(function ($req, $res, $next) {
  if ($req->getQuery('page') === 'api') {
    $req->setHeader('Content-Type', 'application/json');
  } else {
    $req->setHeader('Content-Type', 'text/html');
  }
  return $next($req, $res);
});

// Register a route that accepts GET requests to /?page=home
$rt->addPage('GET', 'home', function () {
  return [
    'title' => 'Hello, world!',
    'description' => 'This is a simple example of a route.',
  ];
});

// Register a route that accepts POST requests to /?page=api
$rt->addPage('POST', 'api', function ($req) {
  $data = $req->getBody();
  return [
    'user' => $data,
  ];
});

$data = $rt->run();

if (isset($data['is_json'])) {
  die(json_encode($data));
}

?>

<html>

<head>
  <title>RT Test</title>
</head>

<body>
  <h1><?= $data['title'] ?></h1>
  <p><?= $data['description'] ?></p>
  <script>
    fetch('/?page=api', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
          name: 'Alice'
        })
        .then(response => response.json())
        .then(data => console.log(data));
    });
  </script>
</body>

</html>