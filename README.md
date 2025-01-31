# Utils

`Utils` is a collection of – somewhat naive – utility classes for PHP applications, including `SQ`, a simple SQLite ORM, and `RT`, a basic routing system.

## Table of Contents

- [Includes](#includes)
- [Installation](#installation)
- [SQ Class](#sq-class)
  - [Using SQ](#using-sq)
  - [Class Documentation](#class-documentation)
- [RT Class](#rt-class)
  - [Using RT](#using-rt)
  - [Class Documentation](#class-documentation-1)

## Includes

- [SQ](#sq-class): A simple SQLite ORM
- [RT](#rt-class): A basic routing system

## Installation

You can install the package via composer:

```bash
composer require janoelze/utils
```

## SQ Class

`SQ` is a simple SQLite ORM that provides methods to interact with SQLite databases effortlessly.

### Using SQ

```php
<?php
use JanOelze\Utils\SQ;

// Initialize the ORM with the SQLite database path
$sq = new SQ('database.sqlite');

// Create a new user
$user = $sq->dispense('user');
$user->name = 'John Doe';
$user->email = 'john.doe@example.com';
$user->save();

// Retrieve users with the name 'John Doe'
$users = $sq->find('user', ['name' => 'John Doe']);
foreach ($users as $user) {
    echo "User ID: {$user->id}, Name: {$user->name}, Email: {$user->email}\n";
}

// Update a user's email
$user = $sq->findOne('user', ['id' => 1]);
if ($user) {
    $user->email = 'new.email@example.com';
    $user->save();
}

// Delete a user
$user = $sq->findOne('user', ['id' => 1]);
if ($user) {
    $user->delete();
}
?>
```

### Class Documentation

- **`dispense(string $type): SQBean`**  
  Create a new bean of the given type.

  **Usage:**

  ```php
  $user = $sq->dispense('user');
  $user->name = 'Jane Doe';
  $user->save();
  ```

- **`find(string $type, array $criteria = []): array`**  
  Find beans based on criteria.

  **Usage:**

  ```php
  $users = $sq->find('user', ['name' => 'Jane Doe']);
  ```

- **`findOne(string $type, array $criteria = []): ?SQBean`**  
  Find a single bean.

  **Usage:**

  ```php
  $user = $sq->findOne('user', ['id' => 1]);
  ```

- **`execute(string $sql, array $params = []): array`**  
  Execute a custom SQL query.

  **Usage:**

  ```php
  $results = $sq->execute('SELECT * FROM user WHERE email = ?', ['jane.doe@example.com']);
  ```

- **`query(string $table): SQQueryBuilder`**  
  Start a query builder for the table.

  **Usage:**

  ```php
  $products = $sq->query('product')
                ->where('price', '>', 100)
                ->orderBy('name')
                ->limit(5)
                ->get();
  ```

- **Transaction Methods:**

  - `beginTransaction()`: Start a transaction.
  - `commit()`: Commit the current transaction.
  - `rollBack()`: Roll back the current transaction.

  **Usage:**

  ```php
  $sq->beginTransaction();
  try {
      // Perform multiple operations
      $user->save();
      $order->save();
      $sq->commit();
  } catch (\Exception $e) {
      $sq->rollBack();
      throw $e;
  }
  ```

- **`getPDO(): PDO`**  
  Retrieve the underlying PDO instance.

  **Usage:**

  ```php
  $pdo = $sq->getPDO();
  ```

- **`ensureTableExists(string $table, SQBean $bean)`**  
  Ensure that a table exists in the database.

  **Usage:**

  ```php
  $sq->ensureTableExists('users', $user);
  ```

- **`addColumn(string $table, string $column, string $type)`**  
  Add a new column to a table.

  **Usage:**

  ```php
  $sq->addColumn('users', 'age', 'INTEGER');
  ```

- **`getTableSchema(string $table): array`**  
  Get the schema of a table.

  **Usage:**

  ```php
  $schema = $sq->getTableSchema('users');
  ```

## RT Class

`RT` is a basic routing system that allows you to define routes and pages for your application. It supports middleware, page handlers, and URL generation. Pages are exposed via the ?page= parameter in the URL, and the default page can be set in the configuration.

### Using RT

```php
<?php

require 'vendor/autoload.php';
use JanOelze\Utils\RT;

// Initialize the routing system with default configuration

$rt = new RT(['default_page' => 'home']);

$rt->addPage('GET', 'home', function() {
    return ['title' => 'Home', 'description' => 'Welcome to the homepage.'];
});

$rt->addPage('GET', 'about-us', function() {
    return ['title' => 'About Us', 'description' => 'Our company information.'];
});

$rt->addPage('POST', 'submit', function($request, $response) {
    $data = $request->getBody();
    // Process form data...
    return ['status' => 'Form submitted successfully.'];
});

$data = $rt->run();

?>
<!DOCTYPE html>
  <head>
      <title><?= $data['title'] ?></title>
  </head>
  <body>
      <nav>
          <ul>
              <li><a href="<?= $rt->getUrl('home') ?>">Home</a></li>
              <li><a href="<?= $rt->getUrl('about-us') ?>">About Us</a></li>
          </ul>
      </nav>
      <h1><?= $data['title'] ?></h1>
      <p><?= $data['description'] ?></p>
  </body>
</html>
```

### Class Documentation

- **`addMiddleware(callable $middleware)`**  
  Add a middleware function.

  **Usage:**

  ```php
  $rt->addMiddleware(function($request, $response, $next) {
      // Perform middleware logic…
      return $next($request, $response);
  });
  ```

- **`addPage(string $method, string $page, callable $handler)`**  
  Define a page/route.

  **Usage:**

  ```php
  // Define a GET route for /?page=dashboard
  $rt->addPage('GET', 'dashboard', function() {
      // Return page data
      return ['title' => 'Dashboard', 'description' => 'User dashboard overview.'];
  });
  ```

- **`getUrl(string $page, array $params = []): string`**  
  Generate a URL for a page.

  **Usage:**

  ```php
  $url = $rt->getUrl('dashboard', ['user_id' => 42]);
  echo "Dashboard URL: {$url}\n";
  ```

- **`run(): array`**  
  Run the routing system and retrieve the response data.

  **Usage:**

  ```php
  $response = $rt->run();
  // Handle the response as needed
  ```

- **`sendJson($data)`**  
  Send a JSON response and terminate the script.

  **Usage:**

  ```php
  $data = ['status' => 'success'];
  $rt->sendJson($data);
  ```

- **`setHeader(string $name, string $value)`**  
  Set an HTTP header.

  **Usage:**

  ```php
  $rt->setHeader('X-Custom-Header', 'Value');
  ```

- **`getCurrentPage(): string`**  
  Get the current page being processed.

  **Usage:**

  ```php
  $currentPage = $rt->getCurrentPage();
  echo "Current Page: {$currentPage}";
  ```

- **`redirect(string $url)`**  
  Redirect to a specified URL and terminate the script.

  **Usage:**

  ```php
  $rt->redirect('http://example.com');
  ```

- **`setConfig(array $config)`**  
  Update the routing configuration.

  **Usage:**

  ```php
  $rt->setConfig(['base_url' => 'https://example.com']);
  ```

- **`getConfig(): array`**  
  Retrieve the current routing configuration.

  **Usage:**

  ```php
  $config = $rt->getConfig();
  print_r($config);
  ```
