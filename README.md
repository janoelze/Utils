# Utils

A PHP library for quick and dirty, single-file web development.

## A simple todo list example

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

## Installation

```bash
composer require janoelze/utils
```

## Class Reference

### RT Class
- __construct(array $config = []): Initializes the RT instance with configuration options.
- addMiddleware(callable $middleware): Registers a middleware to process requests.
- sendJson($data): Sends a JSON response to the client.
- setHeader(string $name, string $value): Sets an HTTP header.
- addPage(string $method, string $page, callable $handler): Registers a page handler for a given HTTP method and page.
- getUrl(string $page, array $params = []): Generates a URL based on the provided page and parameters.
- run(): Processes the current request, executes middlewares and page handlers, and returns the page data.
- getCurrentPage(): Returns the name of the current page.
- redirect(string $url): Redirects the request to a specified URL.
- json($data): Alias for sendJson().

Demo Code:
```php
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

### SQ Class
- __construct(string $path): Creates a new SQLite connection using the specified database file.
- dispense(string $type): Returns a new SQBean instance for the defined table type.
- find(string $type, array $criteria = []): Retrieves records matching specific criteria; returns an array of SQBean objects.
- findOne(string $type, array $criteria = []): Retrieves a single record that matches the criteria.
- execute(string $sql, array $params = []): Executes a raw SQL statement and returns the result set.
- query(string $table): Returns a query builder for constructing custom SQL queries.
- beginTransaction(): Starts a new database transaction.
- commit(): Commits the current transaction.
- rollBack(): Reverts the current transaction.
- getPDO(): Retrieves the PDO instance used for database operations.
- ensureTableExists(string $table, SQBean $bean): Checks if a table exists and creates it if necessary.
- addColumn(string $table, string $column, string $type): Adds a new column to an existing table.
- getTableSchema(string $table): Returns the current schema of the specified table.

Demo Code:
```php
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