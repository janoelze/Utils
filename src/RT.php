<?php

namespace JanOelze\Utils;

class RT
{
  private $config;
  private $middlewares = [];
  private $pages = [];
  private $currentPage;

  public function __construct(array $config = [])
  {
    $this->config = $config;
  }

  public function addMiddleware(callable $middleware)
  {
    $this->middlewares[] = $middleware;
  }

  public function sendJson($data)
  {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit;
  }

  public function setHeader($name, $value)
  {
    header("$name: $value");
  }

  public function addPage($method, $page, callable $handler)
  {
    $this->pages[strtoupper($method)][$page] = $handler;
  }

  public function getUrl(string $page = '', array $params = []): string
  {
    // If no page is supplied, return the current URL with merged query parameters.
    if ($page === '') {
      // If base_url is configured, use it.
      if (!empty($this->config['base_url'])) {
        $baseUrl = rtrim($this->config['base_url'], '/');
        $currentQuery = $_SERVER['QUERY_STRING'] ?? '';
        parse_str($currentQuery, $existingParams);
        $mergedParams = array_merge($existingParams, $params);
        if (!empty($mergedParams)) {
          return "$baseUrl/?" . http_build_query($mergedParams);
        }
        return "$baseUrl/";
      } else {
        // Build current URL from server variables.
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $currentQuery = $_SERVER['QUERY_STRING'] ?? '';
        parse_str($currentQuery, $existingParams);
        $mergedParams = array_merge($existingParams, $params);
        if (!empty($mergedParams)) {
          return "{$scheme}://{$host}{$script}?" . http_build_query($mergedParams);
        }
        return "{$scheme}://{$host}{$script}";
      }
    }

    // If a page is supplied, build URL using the page parameter from config.
    if (!empty($this->config['base_url'])) {
      $query = http_build_query(array_merge([$this->config['page_param'] => $page], $params));
      return rtrim($this->config['base_url'], '/') . "/?{$query}";
    } else {
      $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
      $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
      $script = $_SERVER['SCRIPT_NAME'] ?? '';
      $query = http_build_query(array_merge([$this->config['page_param'] => $page], $params));
      return "{$scheme}://{$host}{$script}?{$query}";
    }
  }

  public function run()
  {
    $request = new Request();
    $page_param = $this->config['page_param'] ?? 'page';

    $pageName = $request->getQuery($page_param, $this->config['default_page'] ?? 'home');
    $this->currentPage = $pageName;
    $method = strtoupper($request->getMethod());

    $middlewareIndex = 0;
    $middlewares = $this->middlewares;

    $runMiddlewares = function ($req) use (&$middlewareIndex, $middlewares, &$runMiddlewares) {
      if (!isset($middlewares[$middlewareIndex])) {
        return null;
      }
      $middleware = $middlewares[$middlewareIndex];
      $middlewareIndex++;
      return $middleware($req, $runMiddlewares);
    };

    $runMiddlewares($request);

    if (isset($this->pages[$method][$pageName])) {
      $handler = $this->pages[$method][$pageName];
      $ref = new \ReflectionFunction($handler);
      $numParams = $ref->getNumberOfParameters();

      if ($numParams === 0) {
        $data = $handler();
      } elseif ($numParams === 1) {
        $data = $handler($request);
      } else {
        $data = $handler($request);
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

  public function getCurrentPage(): string
  {
    return $this->currentPage;
  }

  public function redirect(string $url)
  {
    header("Location: $url");
    exit;
  }

  // Aliases

  public function json($data)
  {
    $this->sendJson($data);
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

  public function getPost($key, $default = null)
  {
    return $_POST[$key] ?? $default;
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
