<?php

namespace JanOelze\Utils;

class CON
{
  /**
   * @var resource The SSH connection resource.
   */
  private $connection;

  /**
   * @var resource The SFTP subsystem resource.
   */
  private $sftp;

  /**
   * Establish a new SSH connection.
   *
   * Options:
   * - protocol: 'ssh' (only supported value)
   * - host: Hostname or IP address.
   * - port: Port number (default: 22).
   * - username: SSH username.
   * - password: Password (optional, if not using key authentication).
   * - key: Path to the private key file (optional, if not using password).
   *         The corresponding public key is assumed to be at the same path with a '.pub' extension.
   * - timeout: Connection timeout in seconds (optional).
   *
   * @param array $options
   * @throws \Exception
   */
  public function __construct(array $options)
  {
    $protocol = $options['protocol'] ?? 'ssh';
    if ($protocol !== 'ssh') {
      throw new \Exception("Unsupported protocol: $protocol");
    }

    $host     = $options['host']     ?? 'localhost';
    $port     = $options['port']     ?? 22;
    $username = $options['username'] ?? '';
    $password = $options['password'] ?? null;
    $key      = $options['key']      ?? null;
    $timeout  = $options['timeout']  ?? 10;

    // Establish SSH connection
    $this->connection = ssh2_connect($host, $port);
    if (!$this->connection) {
      throw new \Exception("Unable to connect to $host:$port");
    }

    // (Optional) If you need to enforce a timeout, you might try to set timeout
    // on the underlying socket. This is not directly supported by ssh2_connect,
    // so advanced users might retrieve the socket and use stream_set_timeout().

    // Authenticate either with key or password.
    if ($key) {
      $pubKey = $key . '.pub';
      if (!file_exists($pubKey)) {
        throw new \Exception("Public key file not found: $pubKey");
      }
      if (!file_exists($key)) {
        throw new \Exception("Private key file not found: $key");
      }
      if (!ssh2_auth_pubkey_file($this->connection, $username, $pubKey, $key)) {
        throw new \Exception("Public key authentication failed for user $username");
      }
    } elseif ($password) {
      if (!ssh2_auth_password($this->connection, $username, $password)) {
        throw new \Exception("Password authentication failed for user $username");
      }
    } else {
      throw new \Exception("No authentication method provided. Supply either a password or key.");
    }

    // Initialize SFTP for file operations.
    $this->sftp = ssh2_sftp($this->connection);
    if (!$this->sftp) {
      throw new \Exception("Could not initialize SFTP subsystem.");
    }
  }

  /**
   * Execute a command on the remote server.
   *
   * @param string $command The command to execute.
   * @return array An associative array with keys: 'stdout', 'stderr', 'exit_code'.
   */
  public function exec($command)
  {
    $stream = \ssh2_exec($this->connection, $command);
    if ($stream === false) {
      return [
        'stdout'    => '',
        'stderr'    => 'Failed to execute command',
        'exit_code' => 1,
      ];
    }

    // Fetch the error stream.
    $errorStream = \ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);

    // Make both streams blocking.
    stream_set_blocking($stream, true);
    stream_set_blocking($errorStream, true);

    $stdout = stream_get_contents($stream);
    $stderr = stream_get_contents($errorStream);

    // Check if the ssh2_get_exit_status function exists.
    if (function_exists('\ssh2_get_exit_status')) {
      $exitCode = \ssh2_get_exit_status($stream);
    } else {
      // Fallback: If the function is not available, default to 0.
      $exitCode = 0;
    }

    fclose($errorStream);
    fclose($stream);

    return [
      'stdout'    => trim($stdout),
      'stderr'    => trim($stderr),
      'exit_code' => $exitCode,
    ];
  }

  /**
   * Download a file from the remote server.
   *
   * @param string $remoteFile The full path of the remote file.
   * @param string $localFile  The destination path on the local system.
   * @throws \Exception if the download fails.
   */
  public function download($remoteFile, $localFile)
  {
    if (!ssh2_scp_recv($this->connection, $remoteFile, $localFile)) {
      throw new \Exception("Failed to download file: $remoteFile to $localFile");
    }
  }

  /**
   * Upload a file or directory to the remote server.
   *
   * For a file, this uses SCP; for a directory, it recursively uploads its contents
   * using SCP and creates directories using the SFTP subsystem.
   *
   * @param string $local  The local file or directory.
   * @param string $remote The destination path on the remote server.
   * @return array An associative array with keys: 'stdout', 'stderr', 'exit_code'.
   */
  public function upload($local, $remote)
  {
    if (is_dir($local)) {
      $result = $this->uploadDirectory($local, $remote);
      if (!$result) {
        return [
          'stdout'    => '',
          'stderr'    => "Failed to upload directory: $local",
          'exit_code' => 1,
        ];
      }
      return [
        'stdout'    => '',
        'stderr'    => '',
        'exit_code' => 0,
      ];
    } elseif (is_file($local)) {
      if (ssh2_scp_send($this->connection, $local, $remote, 0644)) {
        return [
          'stdout'    => '',
          'stderr'    => '',
          'exit_code' => 0,
        ];
      } else {
        return [
          'stdout'    => '',
          'stderr'    => "Failed to upload file: $local to $remote",
          'exit_code' => 1,
        ];
      }
    } else {
      return [
        'stdout'    => '',
        'stderr'    => "Local path does not exist: $local",
        'exit_code' => 1,
      ];
    }
  }

  /**
   * Recursively upload a directory to the remote server.
   *
   * @param string $localDir  The local directory path.
   * @param string $remoteDir The remote directory path.
   * @return bool True on success, false on failure.
   */
  private function uploadDirectory($localDir, $remoteDir)
  {
    // Create the remote directory if it doesn't exist.
    $sftpPath = "ssh2.sftp://{$this->sftp}" . $remoteDir;
    if (!file_exists($sftpPath)) {
      if (!mkdir($sftpPath, 0777, true)) {
        return false;
      }
    }

    $dirIterator = new \DirectoryIterator($localDir);
    foreach ($dirIterator as $fileinfo) {
      if ($fileinfo->isDot()) {
        continue;
      }

      $localPath  = $fileinfo->getPathname();
      $remotePath = rtrim($remoteDir, '/') . '/' . $fileinfo->getFilename();

      if ($fileinfo->isDir()) {
        if (!$this->uploadDirectory($localPath, $remotePath)) {
          return false;
        }
      } elseif ($fileinfo->isFile()) {
        if (!ssh2_scp_send($this->connection, $localPath, $remotePath, 0644)) {
          return false;
        }
      }
    }
    return true;
  }

  /**
   * Close the SSH connection.
   *
   * Note: PHP’s SSH2 extension does not provide an explicit disconnect method.
   * Here we send an "exit" command and clear our connection properties.
   */
  public function close()
  {
    // Sending "exit" may help close the remote session.
    $this->exec('exit');
    $this->connection = null;
    $this->sftp       = null;
  }
}
