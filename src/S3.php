<?php

namespace JanOelze\Utils;

/**
 * S3 is a simple class for working with Amazon S3 buckets.
 */
class S3
{
  protected $bucket;
  protected $region;
  protected $accessKey;
  protected $secretKey;
  protected $endpoint;
  protected $multiCurl;
  protected $curlOpts;

  /**
   * Constructor.
   *
   * Expects a configuration array with the keys:
   * - bucket
   * - region
   * - access_key
   * - secret_key
   * Optionally, you can pass an 'endpoint' to override the default.
   *
   * @param array $config
   */
  public function __construct(array $config)
  {
    if (!isset($config['bucket'], $config['region'], $config['access_key'], $config['secret_key'])) {
      throw new \InvalidArgumentException("Missing required configuration keys.");
    }
    $this->bucket    = $config['bucket'];
    $this->region    = $config['region'];
    $this->accessKey = $config['access_key'];
    $this->secretKey = $config['secret_key'];
    $this->endpoint  = isset($config['endpoint'])
      ? $config['endpoint']
      : "s3.{$this->region}.amazonaws.com";

    $this->multiCurl = curl_multi_init();
    $this->curlOpts  = [
      CURLOPT_CONNECTTIMEOUT  => 30,
      CURLOPT_LOW_SPEED_LIMIT => 1,
      CURLOPT_LOW_SPEED_TIME  => 30,
    ];
  }

  public function __destruct()
  {
    if ($this->multiCurl) {
      curl_multi_close($this->multiCurl);
    }
  }

  /**
   * List files in the bucket.
   *
   * If a glob pattern is provided, only matching keys are returned.
   *
   * @param string|null $pattern
   * @return array
   */
  public function listFiles($pattern = null)
  {
    $response = $this->getBucket();
    if ($response->error) {
      throw new \RuntimeException("Error listing bucket: " . $response->error['message']);
    }
    $xml = simplexml_load_string($response->body);
    if ($xml === false) {
      throw new \RuntimeException("Failed to parse bucket listing XML.");
    }
    $names = [];
    // In the bucket listing, each object is in a <Contents> tag with a <Key>
    foreach ($xml->Contents as $content) {
      $key = (string)$content->Key;
      if ($pattern) {
        if (fnmatch($pattern, $key)) {
          $names[] = $key;
        }
      } else {
        $names[] = $key;
      }
    }
    return $names;
  }

  /**
   * Upload a local file to S3.
   *
   * @param string $source      Path to the local file.
   * @param string $destination S3 key (destination path).
   * @return bool
   */
  public function upload($source, $destination)
  {
    $response = $this->putObject($destination, $source);
    if ($response->error) {
      throw new \RuntimeException("Error uploading file: " . $response->error['message']);
    }
    return true;
  }

  /**
   * Download an S3 file to a local destination.
   *
   * @param string $key         S3 key.
   * @param string $destination Local destination file path.
   * @return bool
   */
  public function download($key, $destination)
  {
    $fh = fopen($destination, 'w');
    if (!$fh) {
      throw new \RuntimeException("Unable to open destination for writing: $destination");
    }
    $response = $this->getObject($key, $fh);
    fclose($fh);
    if ($response->error) {
      throw new \RuntimeException("Error downloading file: " . $response->error['message']);
    }
    return true;
  }

  /**
   * Read the contents of a file from S3.
   *
   * @param string $key S3 key.
   * @return string
   */
  public function read($key)
  {
    $response = $this->getObject($key);
    if ($response->error) {
      throw new \RuntimeException("Error reading file: " . $response->error['message']);
    }
    return $response->body;
  }

  /**
   * Remove a file from the bucket.
   *
   * @param string $key S3 key.
   * @return bool
   */
  public function remove($key)
  {
    $response = $this->deleteObject($key);
    if ($response->error) {
      throw new \RuntimeException("Error removing file: " . $response->error['message']);
    }
    return true;
  }

  /**
   * Remove all files from the bucket.
   *
   * @return bool
   */
  public function removeAll()
  {
    $files = $this->listFiles();
    $errors = [];
    foreach ($files as $file) {
      try {
        $this->remove($file);
      } catch (\Exception $e) {
        $errors[$file] = $e->getMessage();
      }
    }
    if (!empty($errors)) {
      throw new \RuntimeException("Errors occurred during removeAll: " . json_encode($errors));
    }
    return true;
  }

  /**
   * Set permissions (ACL) for an S3 object.
   *
   * @param string $key         S3 key.
   * @param string $permissions e.g. "public-read"
   * @return bool
   */
  public function setPermissions($key, $permissions)
  {
    $response = $this->putObjectAcl($key, $permissions);
    if ($response->error) {
      throw new \RuntimeException("Error setting permissions: " . $response->error['message']);
    }
    return true;
  }

  /**
   * Get the URL for an S3 object.
   *
   * Returns a virtual-hosted–style URL.
   *
   * @param string $key S3 key.
   * @return string
   */
  public function getUrl($key)
  {
    return "https://{$this->bucket}.s3.{$this->region}.amazonaws.com/" . ltrim($key, '/');
  }

  // --- Internal helper methods using S3Request ---

  protected function putObject($path, $file)
  {
    $resource = fopen($file, 'r');
    if (!$resource) {
      throw new \RuntimeException("Unable to open file: $file");
    }
    $uri = $this->bucket . '/' . ltrim($path, '/');
    $request = new S3Request('PUT', $this->endpoint, $uri);
    $request->setFileContents($resource)
      ->useMultiCurl($this->multiCurl)
      ->useCurlOpts($this->curlOpts)
      ->sign($this->accessKey, $this->secretKey);
    $response = $request->getResponse();
    fclose($resource);
    return $response;
  }

  protected function getObject($path, $resource = null)
  {
    $uri = $this->bucket . '/' . ltrim($path, '/');
    $request = new S3Request('GET', $this->endpoint, $uri);
    $request->useMultiCurl($this->multiCurl)
      ->useCurlOpts($this->curlOpts)
      ->sign($this->accessKey, $this->secretKey);
    if (is_resource($resource)) {
      $request->saveToResource($resource);
    }
    return $request->getResponse();
  }

  protected function deleteObject($path)
  {
    $uri = $this->bucket . '/' . ltrim($path, '/');
    $request = new S3Request('DELETE', $this->endpoint, $uri);
    $request->useMultiCurl($this->multiCurl)
      ->useCurlOpts($this->curlOpts)
      ->sign($this->accessKey, $this->secretKey);
    return $request->getResponse();
  }

  protected function getBucket()
  {
    // A GET on the bucket returns an XML listing of objects.
    $uri = $this->bucket;
    $request = new S3Request('GET', $this->endpoint, $uri);
    $request->useMultiCurl($this->multiCurl)
      ->useCurlOpts($this->curlOpts)
      ->sign($this->accessKey, $this->secretKey);
    return $request->getResponse();
  }

  protected function putObjectAcl($path, $acl)
  {
    // To set an ACL on an existing object, issue a PUT request to the ?acl subresource.
    $uri = $this->bucket . '/' . ltrim($path, '/') . '?acl';
    $request = new S3Request('PUT', $this->endpoint, $uri);
    $request->setHeaders(['x-amz-acl' => $acl])
      ->useMultiCurl($this->multiCurl)
      ->useCurlOpts($this->curlOpts)
      ->sign($this->accessKey, $this->secretKey);
    return $request->getResponse();
  }
}

/**
 * S3Request is a helper for constructing and signing HTTP requests to S3.
 */
class S3Request
{
  private $action;
  private $endpoint;
  private $uri;
  private $headers;
  private $curl;
  private $response;
  private $multi_curl;

  public function __construct($action, $endpoint, $uri)
  {
    $this->action   = $action;
    $this->endpoint = $endpoint;
    $this->uri      = $uri;
    $this->headers  = [
      'Content-MD5'  => '',
      'Content-Type' => '',
      'Date'         => gmdate('D, d M Y H:i:s T'),
      'Host'         => $this->endpoint,
    ];
    $this->curl       = curl_init();
    $this->response   = new S3Response();
    $this->multi_curl = null;
  }

  public function saveToResource($resource)
  {
    $this->response->saveToResource($resource);
    return $this;
  }

  /**
   * Sets the file contents for the request.
   *
   * If $file is a resource, it is used as the input stream (PUT upload);
   * otherwise, the string content is used.
   */
  public function setFileContents($file)
  {
    if (is_resource($file)) {
      // Calculate MD5 from the stream.
      $hash_ctx = hash_init('md5');
      hash_update_stream($hash_ctx, $file);
      $stats = fstat($file);
      $fileSize = $stats['size'];
      $md5 = hash_final($hash_ctx, true);
      rewind($file);
      curl_setopt($this->curl, CURLOPT_PUT, true);
      curl_setopt($this->curl, CURLOPT_INFILE, $file);
      curl_setopt($this->curl, CURLOPT_INFILESIZE, $fileSize);
    } else {
      curl_setopt($this->curl, CURLOPT_POSTFIELDS, $file);
      $md5 = md5($file, true);
    }
    $this->headers['Content-MD5'] = base64_encode($md5);
    return $this;
  }

  /**
   * Sets additional HTTP headers.
   */
  public function setHeaders($custom_headers)
  {
    $this->headers = array_merge($this->headers, $custom_headers);
    return $this;
  }

  /**
   * Signs the request using the AWS secret key.
   */
  public function sign($access_key, $secret_key)
  {
    $canonical_amz_headers = $this->getCanonicalAmzHeaders();
    $string_to_sign = $this->action . "\n" .
      $this->headers['Content-MD5'] . "\n" .
      $this->headers['Content-Type'] . "\n" .
      $this->headers['Date'] . "\n";
    if (!empty($canonical_amz_headers)) {
      $string_to_sign .= implode("\n", $canonical_amz_headers) . "\n";
    }
    $string_to_sign .= '/' . $this->uri;
    $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $secret_key, true));
    $this->headers['Authorization'] = "AWS $access_key:$signature";
    return $this;
  }

  public function useMultiCurl($mh)
  {
    $this->multi_curl = $mh;
    return $this;
  }

  public function useCurlOpts($curl_opts)
  {
    curl_setopt_array($this->curl, $curl_opts);
    return $this;
  }

  /**
   * Executes the request and returns an S3Response.
   */
  public function getResponse()
  {
    $http_headers = [];
    foreach ($this->headers as $header => $value) {
      $http_headers[] = "$header: $value";
    }
    curl_setopt_array($this->curl, [
      CURLOPT_USERAGENT      => 'JanOelze S3 PHP Client',
      CURLOPT_URL            => "https://{$this->endpoint}/{$this->uri}",
      CURLOPT_HTTPHEADER     => $http_headers,
      CURLOPT_HEADER         => false,
      CURLOPT_RETURNTRANSFER => false,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_WRITEFUNCTION  => [$this->response, '__curlWriteFunction'],
      CURLOPT_HEADERFUNCTION => [$this->response, '__curlHeaderFunction'],
    ]);

    // Set the proper HTTP method.
    switch ($this->action) {
      case 'DELETE':
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        break;
      case 'HEAD':
        curl_setopt($this->curl, CURLOPT_NOBODY, true);
        break;
      case 'POST':
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        break;
      case 'PUT':
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        break;
    }

    if ($this->multi_curl !== null) {
      curl_multi_add_handle($this->multi_curl, $this->curl);
      $running = null;
      do {
        curl_multi_exec($this->multi_curl, $running);
        curl_multi_select($this->multi_curl);
      } while ($running > 0);
      curl_multi_remove_handle($this->multi_curl, $this->curl);
    } else {
      curl_exec($this->curl);
    }
    $this->response->finalize($this->curl);
    curl_close($this->curl);
    return $this->response;
  }

  /**
   * Builds an array of canonicalized x-amz- headers.
   */
  private function getCanonicalAmzHeaders()
  {
    $canonical_amz_headers = [];
    foreach ($this->headers as $header => $value) {
      $header_lc = trim(strtolower($header));
      $value = trim($value);
      if (strpos($header_lc, 'x-amz-') === 0) {
        $canonical_amz_headers[$header_lc] = "$header_lc:$value";
      }
    }
    ksort($canonical_amz_headers);
    return $canonical_amz_headers;
  }
}

/**
 * S3Response encapsulates an HTTP response from S3.
 */
class S3Response
{
  public $error;
  public $code;
  public $headers;
  public $body;

  public function __construct()
  {
    $this->error   = null;
    $this->code    = null;
    $this->headers = [];
    $this->body    = '';
  }

  /**
   * If saving to a resource, assign it.
   */
  public function saveToResource($resource)
  {
    $this->body = $resource;
  }

  /**
   * Callback for writing response data.
   */
  public function __curlWriteFunction($ch, $data)
  {
    if (is_resource($this->body)) {
      return fwrite($this->body, $data);
    } else {
      $this->body .= $data;
      return strlen($data);
    }
  }

  /**
   * Callback for collecting response headers.
   */
  public function __curlHeaderFunction($ch, $data)
  {
    $parts = explode(':', $data, 2);
    if (count($parts) == 2) {
      list($key, $value) = $parts;
      $this->headers[trim($key)] = trim($value);
    }
    return strlen($data);
  }

  /**
   * Finalizes the response after curl execution.
   */
  public function finalize($ch)
  {
    if (is_resource($this->body)) {
      rewind($this->body);
    }
    if (curl_errno($ch)) {
      $this->error = [
        'code'    => curl_errno($ch),
        'message' => curl_error($ch),
      ];
    } else {
      $this->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
      // If there was an error and we received XML, try to extract error details.
      if ($this->code >= 300 && strpos($content_type, 'application/xml') !== false) {
        if (is_resource($this->body)) {
          $bodyContent = stream_get_contents($this->body);
          rewind($this->body);
        } else {
          $bodyContent = $this->body;
        }
        $responseXml = simplexml_load_string($bodyContent);
        if ($responseXml) {
          $error = [
            'code'    => (string)$responseXml->Code,
            'message' => (string)$responseXml->Message,
          ];
          if (isset($responseXml->Resource)) {
            $error['resource'] = (string)$responseXml->Resource;
          }
          $this->error = $error;
        }
      }
    }
  }
}