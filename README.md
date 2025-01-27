# Utils

`Utils` is a collection of – somewhat naive – utility classes for PHP applications, including `SQ`, a simple SQLite ORM, and `RT`, a basic routing system.

## Includes

- [SQ](#sq-class): A simple SQLite ORM
- [RT](#rt-class): A basic routing system

## Installation

You can install the package via composer:

```bash
composer require janoelze/utils
```

## Usage

### SQ Usage Example

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

### RT Usage Example

```php
<?php
use JanOelze\Utils\RT;

// Initialize the routing system with default configuration
$rt = new RT(['default_view' => 'home']);

// Add middleware for authentication
$rt->addMiddleware(function($request, $response, $next) {
    // Authentication logic here
    // ...
    return $next($request, $response);
});

// Define a GET route for the home view
$rt->addView('GET', 'home', function() {
    return ['title' => 'Home', 'description' => 'Welcome to the homepage.'];
});

// Define a POST route for submitting a form
$rt->addView('POST', 'submit', function($request, $response) {
    $data = $request->getBody();
    // Process form data
    // ...
    return ['status' => 'Form submitted successfully.'];
});

// Generate a URL for the home view with parameters
$url = $rt->getUrl('home', ['ref' => 'newsletter']);
echo "Home URL: {$url}\n";

// Run the routing system
$response = $rt->run();
if ($response['is_json'] ?? false) {
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    echo "Title: {$response['title']}\nDescription: {$response['description']}\n";
}
?>
```

### SQ Class

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

## RT Class

### RT Usage Example

```php
<?php

require 'vendor/autoload.php';
use JanOelze\Utils\RT;

// Initialize the routing system with default configuration

$rt = new RT(['default_view' => 'home']);

$rt->addView('GET', 'home', function() {
    return ['title' => 'Home', 'description' => 'Welcome to the homepage.'];
});

$rt->addView('GET', 'about-us', function() {
    return ['title' => 'About Us', 'description' => 'Our company information.'];
});

$rt->addView('POST', 'submit', function($request, $response) {
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

### RT Class

- **`addMiddleware(callable $middleware)`**  
  Add a middleware function.

  **Usage:**

  ```php
  $rt->addMiddleware(function($request, $response, $next) {
      // Perform middleware logic…
      return $next($request, $response);
  });
  ```

- **`addView($method, $view, callable $handler)`**  
  Define a view/route.

  **Usage:**

  ```php
  // Define a GET route for /?view=dashboard
  $rt->addView('GET', 'dashboard', function() {
      // Return view data
      return ['title' => 'Dashboard', 'description' => 'User dashboard overview.'];
  });
  ```

- **`getUrl(string $view, array $params = []): string`**  
  Generate a URL for a view.

  **Usage:**

  ```php
  $url = $rt->getUrl('dashboard', ['user_id' => 42]);
  echo "Dashboard URL: {$url}\n";
  ```

- **`run()`**  
  Run the routing system.

  **Usage:**

  ```php
  $response = $rt->run();
  // Handle the response as needed
  ```
