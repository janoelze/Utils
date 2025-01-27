<?php

namespace JanOelze\Utils;

class RT
{
  private $config;
  private $middlewares = [];
  private $pages = [];

  public function __construct(array $config = [])
  {
    $this->config = $config;
  }

  public function addMiddleware(callable $middleware)
  {
    $this->middlewares[] = $middleware;
  }

  public function addPage($method, $page, callable $handler)
  {
    $this->pages[strtoupper($method)][$page] = $handler;
  }

  public function getUrl(string $page, array $params = []): string
  {
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $query = http_build_query(array_merge(['page' => $page], $params));
    return "{$scheme}://{$host}{$script}?{$query}";
  }

  public function run()
  {
    $request = new Request();
    $response = new Response();

    $pageName = $request->getQuery('page', $this->config['default_page'] ?? 'home');
    $method = strtoupper($request->getMethod());

    $middlewareIndex = 0;
    $middlewares = $this->middlewares;

    $runMiddlewares = function ($req, $res) use (&$middlewareIndex, $middlewares, &$runMiddlewares) {
      if (!isset($middlewares[$middlewareIndex])) {
        return null;
      }
      $middleware = $middlewares[$middlewareIndex];
      $middlewareIndex++;
      return $middleware($req, $res, $runMiddlewares);
    };

    $runMiddlewares($request, $response);

    if (isset($this->pages[$method][$pageName])) {
      $handler = $this->pages[$method][$pageName];
      $ref = new \ReflectionFunction($handler);
      $numParams = $ref->getNumberOfParameters();

      if ($numParams === 0) {
        $data = $handler();
      } elseif ($numParams === 1) {
        $data = $handler($request);
      } else {
        $data = $handler($request, $response);
      }
    } else {
      $data = [
        'title' => '404 Not Found',
        'description' => 'No matching page found.',
      ];
    }

    if ($request->getHeader('Content-Type') === 'application/json') {
      $data['is_json'] = true;
    }

    return $data;
  }
}

class Request
{
  private $headers = [];

  public function getQuery($key, $default = null)
  {
    return $_GET[$key] ?? $default;
  }

  public function getMethod()
  {
    return $_SERVER['REQUEST_METHOD'] ?? 'GET';
  }

  public function getBody()
  {
    $input = file_get_contents('php://input');
    $decoded = json_decode($input, true);
    return ($decoded === null && json_last_error() !== JSON_ERROR_NONE) ? $input : $decoded;
  }

  public function setHeader($name, $value)
  {
    $this->headers[$name] = $value;
  }

  public function getHeader($name)
  {
    return $this->headers[$name] ?? null;
  }
}

class Response {}
