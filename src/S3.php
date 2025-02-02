<?php

namespace JanOelze\Utils;

class S3
{
  protected $bucket;
  protected $region;
  protected $accessKey;
  protected $secretKey;
  protected $host;

  public function __construct(array $config)
  {
    $this->bucket    = $config['bucket'];
    $this->region    = $config['region'];
    $this->accessKey = $config['access_key'];
    $this->secretKey = $config['secret_key'];
    $this->host      = $this->bucket . '.s3.' . $this->region . '.amazonaws.com';
  }

  /**
   * Generate the AWS Signature V4 signing key.
   */
  private function getSignatureKey($key, $date, $region, $service)
  {
    $kDate    = hash_hmac('sha256', $date, 'AWS4' . $key, true);
    $kRegion  = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', $service, $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    return $kSigning;
  }

  /**
   * Helper method to send an HTTP request with AWS signature.
   *
   * @param string $method       HTTP method (GET, PUT, DELETE, etc.)
   * @param string $keyName      S3 object key (or path)
   * @param string $body         Request body (if any)
   * @param array  $extraHeaders Additional headers (e.g. for ACL)
   * @param string $contentType  Content type header (if applicable)
   *
   * @return array ['code' => HTTP status code, 'response' => response body]
   */
  private function sendRequest($method, $keyName, $body = '', $extraHeaders = [], $contentType = 'application/octet-stream')
  {
    $url = "https://{$this->host}/{$keyName}";
    $amzDate     = gmdate('Ymd\THis\Z');
    $shortDate   = gmdate('Ymd');
    $payloadHash = hash('sha256', $body);
    $contentLength = strlen($body);

    // Set up default headers required for signing.
    $headers = [
      'Host'                   => $this->host,
      'x-amz-date'             => $amzDate,
      'x-amz-content-sha256'   => $payloadHash,
    ];

    // For methods that send content, include Content-Type and Content-Length.
    if (in_array($method, ['PUT', 'POST'])) {
      $headers['Content-Type']   = $contentType;
      $headers['Content-Length'] = $contentLength;
    }

    // Merge in any extra headers (e.g. ACL).
    foreach ($extraHeaders as $k => $v) {
      $headers[$k] = $v;
    }

    // Build the canonical headers and signed headers.
    ksort($headers);
    $canonicalHeaders = '';
    $signedHeadersArr = [];
    foreach ($headers as $key => $value) {
      $lowerKey = strtolower($key);
      $canonicalHeaders .= $lowerKey . ':' . $value . "\n";
      $signedHeadersArr[] = $lowerKey;
    }
    $signedHeaders = implode(';', $signedHeadersArr);

    // Construct the canonical request.
    $canonicalRequest =
      $method . "\n"
      . '/' . $keyName . "\n"
      . "\n"  // query string is empty (unless overridden, e.g. for list objects)
      . $canonicalHeaders . "\n"
      . $signedHeaders . "\n"
      . $payloadHash;
    $hashedCanonicalRequest = hash('sha256', $canonicalRequest);

    // Build the string to sign.
    $algorithm       = 'AWS4-HMAC-SHA256';
    $credentialScope = "{$shortDate}/{$this->region}/s3/aws4_request";
    $stringToSign    =
      $algorithm . "\n"
      . $amzDate . "\n"
      . $credentialScope . "\n"
      . $hashedCanonicalRequest;

    // Calculate the signature.
    $signingKey = $this->getSignatureKey($this->secretKey, $shortDate, $this->region, 's3');
    $signature  = hash_hmac('sha256', $stringToSign, $signingKey);

    // Build the Authorization header.
    $authorizationHeader = "{$algorithm} Credential={$this->accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";
    $headers['Authorization'] = $authorizationHeader;

    // Prepare headers for cURL.
    $curlHeaders = [];
    foreach ($headers as $key => $value) {
      $curlHeaders[] = $key . ': ' . $value;
    }

    // Initialize and execute the cURL request.
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($body !== '') {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'response' => $response];
  }

  /**
   * Upload a file to the bucket.
   *
   * @param string $sourceFile Local file path.
   * @param string $targetName Desired S3 object key.
   *
   * @throws \Exception on failure.
   */
  public function upload($sourceFile, $targetName)
  {
    if (!file_exists($sourceFile)) {
      throw new \Exception("File {$sourceFile} does not exist.");
    }
    $fileContent = file_get_contents($sourceFile);
    $mimeType    = mime_content_type($sourceFile);

    $result = $this->sendRequest('PUT', $targetName, $fileContent, [], $mimeType);
    if ($result['code'] != 200) {
      throw new \Exception("Failed to upload file. HTTP Status Code: {$result['code']}\nResponse: {$result['response']}");
    }
    return true;
  }

  /**
   * Download a file from the bucket.
   *
   * @param string $sourceName      S3 object key.
   * @param string $destinationFile Local destination path.
   *
   * @throws \Exception on failure.
   */
  public function download($sourceName, $destinationFile)
  {
    $result = $this->sendRequest('GET', $sourceName);
    if ($result['code'] == 200) {
      file_put_contents($destinationFile, $result['response']);
      return true;
    }
    throw new \Exception("Failed to download file. HTTP Status Code: {$result['code']}\nResponse: {$result['response']}");
  }

  /**
   * Read and return the contents of a file from the bucket.
   *
   * @param string $targetName S3 object key.
   *
   * @return string File contents.
   *
   * @throws \Exception on failure.
   */
  public function read($targetName)
  {
    $result = $this->sendRequest('GET', $targetName);
    if ($result['code'] == 200) {
      return $result['response'];
    }
    throw new \Exception("Failed to read file. HTTP Status Code: {$result['code']}\nResponse: {$result['response']}");
  }

  /**
   * Remove a file from the bucket.
   *
   * @param string $targetName S3 object key.
   *
   * @throws \Exception on failure.
   */
  public function remove($targetName)
  {
    $result = $this->sendRequest('DELETE', $targetName);
    // Some DELETE responses may return 204 (No Content) or 200.
    if ($result['code'] != 204 && $result['code'] != 200) {
      throw new \Exception("Failed to remove file. HTTP Status Code: {$result['code']}\nResponse: {$result['response']}");
    }
    return true;
  }

  /**
   * List files in the bucket.
   * Optionally filter the keys by a glob pattern.
   *
   * @param string|null $pattern Glob pattern to filter keys.
   *
   * @return array List of S3 object keys.
   */
  public function listFiles($pattern = null)
  {
    // List objects using the ListObjectsV2 API.
    // The query string below forces ListObjectsV2 (list-type=2).
    $query = '?list-type=2';
    $url   = "https://{$this->host}/{$query}";
    $amzDate     = gmdate('Ymd\THis\Z');
    $shortDate   = gmdate('Ymd');
    $payloadHash = hash('sha256', '');
    $headers = [
      'Host'                 => $this->host,
      'x-amz-date'           => $amzDate,
      'x-amz-content-sha256' => $payloadHash,
    ];
    ksort($headers);
    $canonicalHeaders = '';
    $signedHeadersArr = [];
    foreach ($headers as $k => $v) {
      $lowerKey = strtolower($k);
      $canonicalHeaders .= $lowerKey . ':' . $v . "\n";
      $signedHeadersArr[] = $lowerKey;
    }
    $signedHeaders = implode(';', $signedHeadersArr);

    // Note: For listing, the canonical URI is "/" and the query string is "list-type=2".
    $canonicalRequest =
      "GET\n" . "/\n" . "list-type=2" . "\n" . $canonicalHeaders . "\n" . $signedHeaders . "\n" . $payloadHash;
    $hashedCanonicalRequest = hash('sha256', $canonicalRequest);

    $algorithm       = 'AWS4-HMAC-SHA256';
    $credentialScope = "{$shortDate}/{$this->region}/s3/aws4_request";
    $stringToSign    = $algorithm . "\n" .
      $amzDate . "\n" .
      $credentialScope . "\n" .
      $hashedCanonicalRequest;
    $signingKey = $this->getSignatureKey($this->secretKey, $shortDate, $this->region, 's3');
    $signature  = hash_hmac('sha256', $stringToSign, $signingKey);
    $authorizationHeader = "{$algorithm} Credential={$this->accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";
    $headers['Authorization'] = $authorizationHeader;

    $curlHeaders = [];
    foreach ($headers as $k => $v) {
      $curlHeaders[] = $k . ': ' . $v;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    // Parse the XML response.
    $xml = simplexml_load_string($response);
    $files = [];
    if ($xml && isset($xml->Contents)) {
      foreach ($xml->Contents as $content) {
        $keyName = (string)$content->Key;
        if ($pattern === null || fnmatch($pattern, $keyName)) {
          $files[] = $keyName;
        }
      }
    }
    return $files;
  }

  /**
   * Remove all files from the bucket.
   *
   * @return bool
   */
  // public function removeAll()
  // {
  //   $files = $this->listFiles();
  //   foreach ($files as $file) {
  //     $this->remove($file);
  //   }
  //   return true;
  // }

  /**
   * Set permissions (ACL) for a file in the bucket.
   *
   * @param string $targetName  S3 object key.
   * @param string $permissions e.g. 'public-read'
   *
   * @throws \Exception on failure.
   */
  // public function setPermissions($targetName, $permissions)
  // {
  //   // Setting ACL via an extra header.
  //   $extraHeaders = [
  //     'x-amz-acl' => $permissions,
  //   ];
  //   // We send a PUT request with an empty body.
  //   $result = $this->sendRequest('PUT', $targetName, '', $extraHeaders);
  //   if ($result['code'] != 200) {
  //     throw new \Exception("Failed to set permissions. HTTP Status Code: {$result['code']}\nResponse: {$result['response']}");
  //   }
  //   return true;
  // }

  /**
   * Get the public URL for a file.
   *
   * @param string $targetName S3 object key.
   *
   * @return string URL for the file.
   */
  public function getUrl($targetName)
  {
    return "https://{$this->host}/{$targetName}";
  }
}