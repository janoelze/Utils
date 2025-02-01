<p align="center">
  <br>
  <img width="245" src="https://i.imgur.com/19k2dv6.png" />
  <br>
</p>

# Utils

A PHP library for quick and dirty, single-file web development.

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

$sq = new SQ('./db.sqlite');
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

## Class Reference

<p align="left">
  <br>
  <img width="130" src="https://i.imgur.com/gld2Dg7.png" />
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
  <img width="130" src="https://i.imgur.com/w1zi5mG.png" />
  <br>
</p>

### SQ Class

SQ is a lightweight SQLite ORM that simplifies database interactions. It automatically
creates tables, manages columns, and provides simple CRUD operations.

#### Key Methods

- `__construct(string $path):`:<br>Initializes the SQLite connection with the specified file.
- `dispense(string $type):`:<br>Returns a new SQBean instance for the given table type.
- `find(string $type, array $criteria = []):`:<br>Retrieves records matching criteria; returns an array of SQBean objects.
- `findOne(string $type, array $criteria = []):`:<br>Retrieves the first matching record.
- `execute(string $sql, array $params = []):`:<br>Executes a raw SQL statement.
- `query(string $table):`:<br>Returns a query builder for constructing custom queries.
- `beginTransaction(), commit(), rollBack():`:<br>Manage transactions.
- `getPDO():`:<br>Returns the underlying PDO instance.
- `ensureTableExists(string $table, SQBean $bean):`:<br>Ensures the table exists (creates it if needed) and caches its schema.
- `addColumn(string $table, string $column, string $type):`:<br>Adds a new column to an existing table.
- `getTableSchema(string $table):`:<br>Returns the cached schema for the table.

#### How SQ Works

When you save a record using an SQBean instance:

1. SQ verifies if the table exists; if not, it creates one.
2. It inspects the fields of the SQBean and automatically adds any missing columns, inferring the data type (INTEGER, REAL, TEXT).
3. Depending on whether the bean is new, SQ either inserts a new record or updates an existing one.

#### Examples

Creating and saving a record:

```php
use JanOelze\Utils\SQ;

$sq = new SQ('./my_database.sqlite');
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
  <img width="130" src="https://i.imgur.com/3HzBMx1.png" />
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

**Built-in rules:**

email, url, ip, ipv4, ipv6, domain, hostname, alpha, alphaNumeric, numeric, integer, float, boolean, hex, base64, json, date, time, dateTime, creditCard, uuid, macAddress, md5, sha1, sha256, sha512, isbn, issn

**Adding custom rules:**

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
  <img width="130" src="https://i.imgur.com/EBjA8wx.png" />
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
  <img width="130" src="https://i.imgur.com/5yN8yUQ.png" />
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

### Prompt Builder

The prompt builder simplifies the creation of multi-message prompts. It allows you to add system messages (which are combined) and multiple user messages separately.

- `addSystemMessage(string $message, array $params = [])`:<br>
  Adds a system message, replacing placeholders with provided parameters.
- `addUserMessage(string $message, array $params = [])`:<br>
  Adds a user message with placeholder replacements.
- `get()`:<br>
  Returns the constructed prompt as an array.
- `__toString()`:<br>
  Provides a string representation for debugging.

### AI & Prompt Builder Example Code

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