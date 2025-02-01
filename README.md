<p align="center">
  <br>
  <img width="210" src="https://i.imgur.com/19k2dv6.png" />
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
  <img width="130" src="https://i.imgur.com/FIrFwWF.png" />
  <br>
</p>

### RT Class

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

Demo Code:

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

- `__construct(string $path)`:<br>
  Creates a new SQLite connection using the specified database file.
- `dispense(string $type)`:<br>
  Returns a new SQBean instance for the defined table type.
- `find(string $type, array $criteria = [])`:<br>
  Retrieves records matching specific criteria; returns an array of SQBean objects.
- `findOne(string $type, array $criteria = [])`:<br>
  Retrieves a single record that matches the criteria.
- `execute(string $sql, array $params = [])`:<br>
  Executes a raw SQL statement and returns the result set.
- `query(string $table)`:<br>
  Returns a query builder for constructing custom SQL queries.
- `beginTransaction()`:<br>
  Starts a new database transaction.
- `commit()`:<br>
  Commits the current transaction.
- `rollBack()`:<br>
  Reverts the current transaction.
- `getPDO()`:<br>
  Retrieves the PDO instance used for database operations.
- `ensureTableExists(string $table, SQBean $bean)`:<br>
  Checks if a table exists and creates it if necessary.
- `addColumn(string $table, string $column, string $type)`:<br>
  Adds a new column to an existing table.
- `getTableSchema(string $table)`:<br>
  Returns the current schema of the specified table.

Demo Code:

```php
use JanOelze\Utils\SQ;
// Example usage of SQ:
$sq = new SQ('./database.sqlite');
$user = $sq->dispense('user');
$user->name  = 'Alice';
$user->email = 'alice@example.com';
$user->save();

$users = $sq->find('user', ['name' => 'Alice']);
foreach ($users as $user) {
  echo $user->email;
}
```

<hr>

<p align="left">
  <br>
  <img width="130" src="https://i.imgur.com/WpvTkX2.png" />
  <br>
</p>

### VLD Class

- `__construct()`: Initializes the VLD instance with built-in validation rules.
- `addRule(string $ruleName, callable $callable)`: Adds a custom validation rule.
- `isValid(string $ruleName, $value)`: Validates a value against a specified rule.

**Example usage:**
```php
use JanOelze\Utils\VLD;

$vld = new VLD();
if ($vld->isValid('email', 'test@example.com')) {
    echo "Valid email!";
} else {
    echo "Invalid email!";
}
```

**Built-in Validation Rules:**
- email
- url
- ip
- ipv4
- ipv6
- domain
- hostname
- alpha
- alphaNumeric
- numeric
- integer
- float
- boolean
- hex
- base64
- json
- date
- time
- dateTime
- creditCard
- uuid
- macAddress
- md5
- sha1
- sha256
- sha512
- isbn
- issn

**Extending with Custom Rules**

To add a custom rule, use the `addRule` method. For example, to validate license plates:

```php
$vld->addRule('license-plate', function ($value) {
    return preg_match('/^[A-Z]{1,3}-[0-9]{1,4}$/', $value);
});
if ($vld->isValid('license-plate', 'ABC-1234')) {
    echo "Valid license plate!";
} else {
    echo "Invalid license plate!";
}
```
