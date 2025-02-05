<p align="center">
  <br>
  <img width="245" src="https://i.imgur.com/VHPgRxM.png" />
  <br>
</p>

# Utils

A PHP library for quick and dirty, single-file web development.

## Modules

- [**RT**](#rt-class): URL routing with support for different HTTP methods
- [**SQ**](#sq-class): SQLite interface with magic schema handling
- [**JBS**](#jbs-class): Job scheduler to run tasks at specific intervals
- [**AI**](#ai-class): LLM interface and prompt builder
- [**CH**](#ch-class): File-based cache handler
- [**GR**](#gr-class): Generate SVG charts
- [**JS**](#js-class): Simple JSON store
- [**LG**](#lg-class): Logger with colored output and file logging
- [**VLD**](#vld-class): Validation library with built-in and custom rules
- [**ENV**](#env-class): Get/Set environment variables
- [**FS**](#fs-class): Interact with the file system
- [**S3**](#s3-class): Interact with Amazon S3
- [**CON**](#con-class): Interact with remote servers
- [**THR**](#thr-class): Execute tasks in parallel

## Installation

```bash
composer require janoelze/utils
```

View on [Packagist](https://packagist.org/packages/janoelze/utils)

## Get in loser, we're building a todo list app

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use JanOelze\Utils\RT;
use JanOelze\Utils\SQ;

$sq = new SQ([
  'db' => __DIR__ . '/todo.sqlite',
]);

$rt = new RT([
  'base_url'    => 'http://localhost:8001',
  'page_param'  => 'action',
  'default_page' => 'home',
]);

$rt->addPage('GET', 'home', function () use ($sq) {
  return [
    'title' => 'Todo List',
    'todos' => $sq->find('todo'),
  ];
});

$rt->addPage('POST', 'add-todo', function ($req) use ($rt, $sq) {
  $title = $req->getPost('title');
  if (!$title) $rt->redirect($rt->getUrl('home'));
  $todo = $sq->dispense('todo');
  $todo->title = $title;
  $todo->completed = false;
  $todo->save();
  $rt->redirect($rt->getUrl('home'));
});

$rt->addPage('POST', 'toggle-todo', function ($req) use ($rt, $sq) {
  $todo = $sq->findOne('todo', [
    'uuid' => $req->getPost('uuid') ?? null,
  ]);

  if ($todo) {
    $todo->completed = !$todo->completed;
    $todo->save();
  }

  $rt->redirect($rt->getUrl('home'));
});

$data = $rt->run();

?>
<!DOCTYPE html>
<html>

<head>
  <title><?= $data['title'] ?></title>
</head>

<body>
  <h1><?= $data['title'] ?></h1>
  <?php if (isset($data['todos'])): ?>
    <ul>
      <?php foreach ($data['todos'] as $todo): ?>
        <li>
          <form method="post" action="<?= $rt->getUrl('toggle-todo') ?>">
            <input type="hidden" name="uuid" value="<?= $todo->uuid ?>">
            <label>
              <input type="checkbox" name="completed"
                <?= $todo->completed ? 'checked' : '' ?>
                onchange="this.form.submit()">
              <?= $todo->title ?>
            </label>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <form method="post" action="<?= $rt->getUrl('add-todo') ?>">
    <input type="text" name="title" placeholder="Enter a new todo">
    <button type="submit">Add Todo</button>
  </form>
</body>

</html>
```

## Module Reference

<p align="left">
  <br>
  <img width="130" src="https://i.imgur.com/o2rOr1z.png" />
  <br>
</p>

### RT Class

`RT` is a simple router, allowing you to define URL handlers for different HTTP methods.

- `__construct(array $config = [])`:<br>
  Initializes the RT instance with configuration options.
- `addMiddleware(callable $middleware)`:<br>
  Registers a middleware to process requests.
- `sendJson($data)`:<br>
  Sends a JSON response to the client.
- `setHeader(string $name, string $value)`:<br>
  Sets an HTTP header.
- `addPage(string $method, string $page, callable $handler)`:<br>
  Registers a page handler for a given HTTP method and page.
- `getUrl(string $page, array $params = [])`:<br>
  Generates a URL based on the provided page and parameters.
- `run()`:<br>
  Processes the current request, executes middlewares and page handlers, and returns the page data.
- `getCurrentPage()`:<br>
  Returns the name of the current page.
- `redirect(string $url)`:<br>
  Redirects the request to a specified URL.
- `json($data)`:<br>
  Alias for sendJson().

```php
use JanOelze\Utils\RT;
// Example usage of RT:
$rt = new RT([
  'base_url'    => 'http://localhost:8000',
  'page_param'  => 'action',
  'default_page'=> 'home',
]);
$rt->addPage('GET', 'home', function() {
  return ['title' => 'Welcome', 'content' => 'Hello World!'];
});
$data = $rt->run();
echo $data['title'];
```

<hr>

<p align="left">
  <br>
  <img width="130" src="https://i.imgur.com/vBwMYVd.png" />
  <br>
</p>

### SQ Class

SQ is a lightweight SQLite ORM that simplifies database interactions. It automatically
creates tables, manages columns, and provides simple CRUD operations.

#### Key Methods

- `__construct(string $path):`:<br>Initializes the SQLite connection with the specified file.
- `dispense(string $type):`:<br>Returns a new SQRecord instance for the given table type.
- `find(string $type, array $criteria = []):`:<br>Retrieves records matching criteria; returns an array of SQRecord objects.
- `findOne(string $type, array $criteria = []):`:<br>Retrieves the first matching record.
- `execute(string $sql, array $params = []):`:<br>Executes a raw SQL statement.
- `query(string $table):`:<br>Returns a query builder for constructing custom queries.
- `beginTransaction(), commit(), rollBack():`:<br>Manage transactions.
- `getPDO():`:<br>Returns the underlying PDO instance.
- `ensureTableExists(string $table, SQRecord $record):`:<br>Ensures the table exists (creates it if needed) and caches its schema.
- `addColumn(string $table, string $column, string $type):`:<br>Adds a new column to an existing table.
- `getTableSchema(string $table):`:<br>Returns the cached schema for the table.

#### How SQ Works

When you save a record using an SQRecord instance:

1. SQ verifies if the table exists; if not, it creates one.
2. It inspects the fields of the SQRecord and automatically adds any missing columns, inferring the data type (INTEGER, REAL, TEXT).
3. Depending on whether the record is new, SQ either inserts a new record or updates an existing one.

#### Examples

Creating and saving a record:

```php
use JanOelze\Utils\SQ;

$sq = new SQ(['db' => './my_database.sqlite']);  // Changed initialization
$user = $sq->dispense('user');
$user->name  = 'Alice';
$user->email = 'alice@example.com';
$user->save();
```

Fetching records:

```php
// Find all users with the name 'Alice'
$users = $sq->find('user', ['name' => 'Alice']);

// Find a single user by email
$user = $sq->findOne('user', ['email' => 'alice@example.com']);
```

Building a custom query:

```php
$results = $sq->query('user')
    ->where('email', 'LIKE', '%@example.com')
    ->orderBy('id', 'DESC')
    ->limit(5)
    ->get();
```

Using transactions:

```php
try {
    $sq->beginTransaction();

    $user = $sq->dispense('user');
    $user->name  = 'Bob';
    $user->email = 'bob@example.com';
    $user->save();

    // ... other operations ...

    $sq->commit();
} catch (\Exception $e) {
    $sq->rollBack();
    // Handle exception as needed.
}
```

<hr>

<p align="left">
  <br>
  <img width="130" src="https://i.imgur.com/KZhvvcy.png" />
  <br>
</p>

### JBS Class

`JBS` allows you to schedule and run jobs at specific intervals. It uses SQLite to store job information and run history.

- `__construct(array $options = [])`:<br>
  Initializes the JBS instance. Options include:
  - `db`: the SQLite database file path (default: `./jobs.sqlite`)
  - `retention`: the number of seconds to keep run records (default: 1 week)
- `schedule(string|false $interval, string $jobId, callable $callback)`:<br>
  Registers a job. Use a human‑readable interval (e.g., "30s", "1m") or `false` for manual execution.
- `onFailure(callable $callback)`:<br>
  Sets a callback that is called after a job has failed 3 times. The callback receives the job ID and the output from the final attempt.
- `run()`:<br>
  Executes all scheduled jobs with a valid interval. Typically triggered by a cron job.
- `runJob(string $jobId)`:<br>
  Manually executes a job by its identifier.
- `clear()`:<br>
  Clears all registered jobs.

```php
use JanOelze\Utils\JBS;

$jbs = new JBS(['db' => './jobs.sqlite']);

// Schedule a job to run every 30 seconds.
$jbs->schedule('30s', 'fetch-news', function () {
    echo "Fetching news updates...\n";
});

// Run scheduled jobs, trigger this from a cron job, for example.
$jbs->run();

// Manually run a job.
$jbs->runJob('fetch-news');
```

<hr>

<p align="left">
  <br>
  <img width="130" src="https://i.imgur.com/9pdP8Kl.png" />
  <br>
</p>

### AI Class

`AI` is a class that interfaces with various LLM providers (currently only OpenAI) to generate responses based on prompts.

- `__construct(array $config = [])`:<br>
  Initializes the AI instance using a provider based on the given configuration.
- `generate($prompt, array $params = [])`:<br>
  Generates a response. The method formats the prompt as follows:
  - A single message is treated as a user message.
  - Two messages with the first being a system message and the second a user message.
- `prompt()`:<br>
  Returns a new prompt builder instance.

```php
use JanOelze\Utils\AI;

// Create a new instance of AI, using the OpenAI endpoint.
$ai = new AI([
  'platform' => [
    'name' => 'openai',
    'api_key' => $api_key,
    'model' => 'gpt-4o-mini'
  ]
]);

// Query the AI with a simple prompt.
$res = $ai->generate(['What is the meaning of life?']);

// Check for errors and output the response.
if (!isset($res['error'])) {
  echo $res['response'];
} else {
  echo 'An error occurred: ' . $res['error'];
}

// Query the AI with a user and system message.
$res = $ai->generate(["You're a chatbot.", "What is the meaning of life?"]);

print_r($res);

// Params can be passed to the prompt.
$res = $ai->generate(['What is the meaning of life?'], ['temperature' => 0.5, 'model' => 'gpt-4o']);
print_r($res);
```

#### Prompt Builder

The prompt builder simplifies the creation of multi-message prompts. It handles replacing placeholders in messages and provides a convenient way to construct prompts.

- `addSystemMessage(string $message, array $params = [])`:<br>
  Adds a system message, replacing placeholders with provided parameters.
- `addUserMessage(string $message, array $params = [])`:<br>
  Adds a user message with placeholder replacements.
- `get()`:<br>
  Returns the constructed prompt as an array.
- `__toString()`:<br>
  Provides a string representation for debugging.

```php
// Create a new prompt
$prompt = $ai->prompt();

// Add a system message line
$prompt->addSystemMessage("Today's secret code is :code", ['code' => rand(1000, 9999)]);

// Add a user message
$prompt->addUserMessage("What is the secret code?");

// Get the prompt as an array
print_r($prompt->get());
// [
//   "Today's secret code is 7878."
//   "What is the secret code?"
// ]

// Generate the response
$res = $ai->generate($prompt->get());

if (!isset($res['error'])) {
  echo $res['response'];
} else {
  echo 'An error occurred: ' . $res['error'];
}
```

<hr>

<p align="left">
  <br>
  <img width="130" src="https://i.imgur.com/q8js5rm.png" />
  <br>
</p>

### CH Class

`CH` is a cache handler that stores data in files.

**Constructor:**

- \_\_construct(array $config)
  - Requires a 'dir' option for the cache directory.
  - Creates the cache directory if it does not exist.

**Methods:**

- `set(string $key, mixed $value, int|string $expiration)`:<br>
  Stores a value with a specified expiration time (numeric seconds or human-readable string like "1d").
- `get(string $key)`:<br>
  Retrieves the value associated with the given key, or returns null if the cache entry is missing or expired.
- `clear(string|null $key = null)`:<br>
  Clears a specific cache entry if a key is provided, otherwise clears all cache files.
- `getFilePath(string $key)`:<br>
  Generates and returns the file path for a given cache key.
- `parseExpiration(int|string $expiration)`:<br>
  Converts an expiration time (numeric or human-readable) into a Unix timestamp.

```php
use JanOelze\Utils\CH;

// Initialize the cache handler with a cache directory.
$ch = new CH(['dir' => __DIR__ . '/cache']);

// Store a value with a 1-hour expiration
$ch->set('key', 'value', 3600);

// Retrieve the value
echo $ch->get('key');

// You can also store arrays, or use human-readable expiration times
$ch->set('key2', ['a' => 1, 'b' => 2], '1d');

// Retrieve the array item `a`, it will be restored as an array
echo $ch->get('key2')['a'];
```

<hr>

<p align="left">
  <br>
  <img width="130" src="https://i.imgur.com/l1l6lIU.png" />
  <br>
</p>

## GR Class

`GR` creates SVG charts.

### Methods

- `plot(array $config)`:<br>Builds the SVG chart based on the provided configuration.
- `output()`: <br>Returns the generated SVG as a string.
- `save(string $filePath)`:<br>Saves the SVG output to the specified file.

### Line Chart

```php
use JanOelze\Utils\GR;

// Initialize the GR library
$gr = new GR();

$data = [];

for ($i = 0; $i < 100; $i++) {
  $data[] = [$i, rand(0, 100)];
}

// Create a simple sparkline chart
$gr->plot([
  'type' => 'line',
  'animate' => 1000, // Animate the line drawing (in ms, optional)
  'style' => [
    'container' => [
      'width' => 300,
      'height' => 50,
    ],
  ],
  'datasets' => [
    [
      'values' => $data
    ]
  ],
]);

// Save the generated SVG to a file
$gr->save('./static/sparkline.svg');
```

### Resulting SVG

![](static/sparkline.svg)

<hr>

<p align="left">
  <br>
  <img width="130" src="https://i.imgur.com/62mxt8c.png" />
  <br>
</p>

## JS Class

`JS` is a thread-safe (finger's crossed) JSON store. It provides simple methods to retrieve, modify, and clear JSON data.

**Methods:**
- `getAll(): array`:<br>Returns the entire data store.
- `getKeys(): array`:<br>Returns all keys in the store.
- `get(string $key)`:<br>Retrieves the value for a specific key.
- `set(string $key, mixed $value): void`:<br>Sets or updates a key with a value.
- `delete(string $key): void`:<br>Deletes a key from the store.
- `clear(): void`:<br>Clears the entire store.

```php
use JanOelze\Utils\JS;

$js = new JS('/path/to/store.json');

// Set a value
$js->set('name', 'John Doe');

// Get a value
echo $js->get('name');

// Get all keys
print_r($js->getKeys());

// Get all data
print_r($js->getAll());

// Delete a key
$js->delete('name');

// Clear the store
$js->clear();
```

<hr>

<p align="left">
  <br>
  <img width="130" src="https://i.imgur.com/gLu1DRe.png" />
  <br>
</p>

### LG Class

LG is a logger that directs output to the console or files. It supports log levels and ANSI colors for console output.

- `__construct(array $config = [])`:<br>
  Initializes the logger with options:  
  • date_format: PHP date() format for timestamps.  
  • colors: Enable/disable ANSI colors for console output.  
  • destinations: An array specifying "console" or file paths for logging.

- `log(...$messages)`:<br>
  Logs a message at the "LOG" level. Accepts multiple arguments that are concatenated and pretty-prints arrays/objects.

- `print(...$messages)`:<br>
  Alias for log(...$messages).

- `write(...$messages)`:<br>
  Alias for log(...$messages).

- `warn(...$messages)`:<br>
  Logs a warning message at the "WRN" level.

- `error(...$messages)`:<br>
  Logs an error message at the "ERR" level.

- `success(...$messages)`:<br>
  Logs a success message at the "SCS" level.

- `debug(...$messages)`:<br>
  Logs a debug message at the "DBG" level.

```php
use JanOelze\Utils\LG;

// Initialize the logger with console and file destinations.
$lg = new LG([
  'date_format' => 'd-m-Y H:i:s',
  'colors'      => true,
  'destinations'=> ['console', '/path/to/logfile.log']
]);

// Logs a message at the "LOG" level.
$lg->log('A simple log message');

// Arguments are concatenated and arrays/objects are pretty-printed.
$lg->warn('Retried', 3, 'times');
$lg->error('An error occurred:', $error);
```

<hr>

<p align="left">
  <br>
  <img width="130" src="https://i.imgur.com/qmgRCZi.png" />
  <br>
</p>

### VLD Class

`VLD` is a simple validation library. It comes with many built-in validation rules, but is easily extendable with custom rules.

- `__construct()`: Initializes the VLD instance with built-in validation rules.
- `addRule(string $ruleName, callable $callable)`: Adds a custom validation rule.
- `isValid(string $ruleName, $value)`: Validates a value against a specified rule.

```php
use JanOelze\Utils\VLD;

// Initialize the VLD instance.
$vld = new VLD();

// Validate an email address.
if ($vld->isValid('email', 'test@example.com')) {
    echo "Valid email!";
} else {
    echo "Invalid email!";
}
```

#### Built-in rules:

email, url, ip, ipv4, ipv6, domain, hostname, alpha, alphaNumeric, numeric, integer, float, boolean, hex, base64, json, date, time, dateTime, creditCard, uuid, macAddress, md5, sha1, sha256, sha512, isbn, issn

#### Adding custom rules:

To add a custom rule, use the `addRule` method. For example, to validate license plates:

```php
use JanOelze\Utils\VLD;

// Register a custom rule for license plates.
$vld->addRule('licensePlate', function ($value) {
    return preg_match('/^[A-Z]{1,3}-[0-9]{1,4}$/', $value);
});

// Validate a license plate.
if ($vld->isValid('licensePlate', 'ABC-1234')) {
    echo "Valid license plate!";
} else {
    echo "Invalid license plate!";
}
```

<hr>

<p align="left">
  <br>
  <img width="130" src="https://i.imgur.com/8kSj05L.png" />
  <br>
</p>

### ENV Class

`ENV` manages environment variables. It loads global environment variables and merges them with values from a provided .env file if it exists.

- `__construct(?string $envFilePath = null)`:<br>
  Initializes the ENV instance by loading global environment variables and merging them with values from a provided .env file if it exists.
- `get(?string $key = null)`:<br>
  Retrieves a specific environment variable when a key is provided, or returns all environment variables otherwise.
- `set(string $key, $value)`:<br>
  Sets an environment variable in the internal storage and updates the system environment.

```php
use JanOelze\Utils\ENV;

// Initialize the ENV instance with a .env file.
$env = new ENV(__DIR__ . '/.env');

// Get all environment variables.
print_r($env->get());

// Get the value of a specific environment variable.
echo $env->get('APP_ENV');

// Set a new environment variable.
$env->set('DEBUG', true);
```

<hr>

<p align="left">
  <br>
  <img width="130" src="https://i.imgur.com/QdYWIb5.png" />
  <br>
</p>

### FS Class

`FS` is a utility class for file system operations.

- `listFiles(string $pattern): array`:<br>
  Lists files matching a given glob pattern.

- `copy(string $source, string $destination): bool`:<br>
  Copies a file or directory. If copying a directory, the operation is recursive.

- `createDirectory(string $directory, int $permissions = 0777): bool`:<br>
  Recursively creates a directory with specified permissions.

- `remove(string $path): bool`:<br>
  Removes a file or directory. If a directory, removal is recursive.

- `write(string $path, string $content): bool`:<br>
  Writes content to a file, overwriting any existing content.

- `append(string $path, string $content): bool`:<br>
  Appends content to an existing file.

- `read(string $path): string`:<br>
  Reads and returns the content of a file.

- `zip(string $source, string $destination): bool`:<br>
  Zips a directory into a zip file.

- `info(string $path): array`:<br>
  Returns rich information about a file.

## Example

```php
use JanOelze\Utils\FS;

$fs = new FS();

// List all PHP files in the current directory.
$files = $fs->listFiles('*.php');

// Copy a file.
$fs->copy('source.txt', 'destination.txt');

// Create a new directory.
$fs->createDirectory('new_directory');

// Remove a file.
$fs->remove('file.txt');

// Write content to a file.
$fs->write('file.txt', 'Hello, world!');

// Append content to a file.
$fs->append('file.txt', 'Goodbye, world!');

// Remove a directory.
$fs->remove('directory');

// Or file…
$fs->remove('file.txt');

// Read the content of a file.
echo $fs->read('file.txt');

// Zip the directory '/path/to/dir' into 'archive.zip'
if ($fs->zip('/path/to/dir', 'archive.zip')) {
    echo "Directory zipped successfully!";
}
```

```php
use JanOelze\Utils\FS;

$fs = new FS();

// Get rich information about a file.
$info = $fs->info('/tmp/hello.txt');

// => Array (
//     [path] => /tmp/hello.txt
//     [basename] => hello.txt
//     [exists] => 1
//     [realpath] => /private/tmp/hello.txt
//     [filename] => hello
//     [extension] => txt
//     [type] => file
//     [size] => 13
//     [inode] => 141667381
//     [device] => 16777234
//     [link_count] => 1
//     [block_size] => 4096
//     [blocks] => 8
//     [atime] => 1738757881
//     [mtime] => 1738758051
//     [ctime] => 1738758051
//     [creation_time] =>
//     [permissions] => 33188
//     [owner] => janoelze
//     [group] => wheel
//     [readable] => 1
//     [writable] => 1
//     [mime_type] => text/plain
//     [hash_sha256] => 315f5bdb76d078c43b8ac0064e4a0164612b1fce77c869345bfc94c75894edd3
// )
```

<hr>

<p align="left">
  <br>
  <img width="130" src="https://i.imgur.com/8hUJKmS.png" />
  <br>
</p>

### S3 Class

S3 is a simple class for working with Amazon S3 buckets.

#### Key Methods

- `__construct(array $config)`:<br>
  Initializes the S3 instance with configuration options including bucket name, region, access key, and secret key.
- `upload(string $sourceFile, string $targetName)`:<br>
  Uploads a local file to the S3 bucket.
- `download(string $sourceName, string $destinationFile)`:<br>
  Downloads a file from the S3 bucket to a local destination.
- `read(string $targetName)`:<br>
  Reads and returns the contents of a file from the bucket.
- `remove(string $targetName)`:<br>
  Deletes a file from the bucket.
- `listFiles(string $pattern = null)`:<br>
  Retrieves a list of file keys in the bucket, optionally filtering with a glob pattern.
- `getUrl(string $targetName)`:<br>
  Returns the public URL for the specified file.

```php
use JanOelze\Utils\S3;
use JanOelze\Utils\ENV;

$env = new ENV(__DIR__ . '/.env');

// Initialize the S3 instance.
$s3 = new S3([
  'bucket'     => $env->get('AWS_BUCKET'),
  'region'     => $env->get('AWS_REGION'),
  'access_key' => $env->get('AWS_ACCESS_KEY_ID'),
  'secret_key' => $env->get('AWS_SECRET_ACCESS_KEY')
]);

// Upload a file
$s3->upload('/tmp/diagnostics.log', 'logs/diagnostics.log');

// List all log files
foreach ($s3->listFiles('logs/*.log') as $file) {
  echo $file . "\n";
}

// Download a file
$s3->download('logs/diagnostics.log', '/tmp/diagnostics.log');

// Read a file
echo $s3->read('logs/diagnostics.log');

// Remove a file
$s3->remove('logs/diagnostics.log');

// Get the public URL for a file
echo $s3->getUrl('logs/diagnostics.log');
```

<hr>

<p align="left">
  <br>
  <img width="130" src="https://i.imgur.com/heGiGIX.png" />
  <br>
</p>

### CON Class

`CON` is a simple SSH and SFTP wrapper that facilitates executing remote commands and transferring files.

- `__construct(array $options)`:<br>
  Establishes an SSH connection using options such as host, port, username, password or key. Throws an exception if the connection or authentication fails.

- `exec(string $command)`:<br>
  Executes a remote command and returns an associative array with keys: `stdout`, `stderr`, and `exit_code`.

- `download(string $remoteFile, string $localFile)`:<br>
  Uses SCP to download a file from the remote server.

- `upload(string $local, string $remote)`:<br>
  Uploads a local file or directory (recursively) to the remote server. Returns status based on success or failure.

- `close()`:<br>
  Closes the SSH session by sending an "exit" command and clearing connection resources.

```php
use JanOelze\Utils\CON;

$con = new CON([
  'host'     => 'example.com',
  'port'     => 22,
  'username' => 'user',
  'password' => 'password'
]);

// Execute a remote command
$res = $con->exec('ls -la');

// Check for errors
if ($res['exit_code'] !== 0) {
  print_r($res['stderr']);
  exit;
}

// Output the result
echo $res['stdout'];

// Download a file
$con->download('/remote/file.txt', '/local/file.txt');

// Upload a file
$con->upload('/local/file.txt', '/remote/file.txt');

// Close the connection
$con->close();
```

<hr>

<p align="left">
  <br>
  <img width="130" src="https://i.imgur.com/1fpaW7k.png" />
  <br>
</p>

### THR Class

`THR` allows you to execute tasks in parallel using forking and socket-based IPC.

- `__construct(array $config)`:<br>
  Sets the maximum number of workers (default: 4).
- `submit(callable $task, mixed $input)`:<br>
  Forks a child process to execute a task.
- `wait()`:<br>
  Waits for all tasks to finish and collects their results.

```php
use JanOelze\Utils\THR;

// Initialize the pool with a maximum of 3 workers.
$pool = new THR(['max_workers' => 3]);

// Define a task that takes an input and returns a result.
$task = function ($input) {
    sleep(1);
    return $input * 2;
};

// Submit tasks to the pool.
foreach (range(1, 5) as $i) {
    $pool->submit($task, $i);
}

// Wait for all tasks to finish and collect the results.
$results = $pool->wait();
// => [2, 4, 6, 8, 10]
```