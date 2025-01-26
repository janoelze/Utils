<?php

namespace JanOelze\Utils;

class RT
{
  private $config;
  private $middlewares = [];
  private $views = [];

  public function __construct(array $config = [])
  {
    $this->config = $config;
  }

  public function addMiddleware(callable $middleware)
  {
    $this->middlewares[] = $middleware;
  }

  public function addView($method, $view, callable $handler)
  {
    $this->views[strtoupper($method)][$view] = $handler;
  }

  public function run()
  {
    $request = new Request();
    $response = new Response();

    $viewName = $request->getQuery('view', $this->config['default_view'] ?? 'home');
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

    if (isset($this->views[$method][$viewName])) {
      $handler = $this->views[$method][$viewName];
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
        'description' => 'No matching route/view found.',
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
