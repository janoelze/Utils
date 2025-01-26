# SQ - A Small & Quick SQLite-Only ORM

SQ is a lightweight ORM inspired by RedBeanPHP, designed for rapid prototyping and mini PHP applications using SQLite.

## Features

- Simple and intuitive API
- Dynamic table and column creation
- Automatic handling of `created_at`, `updated_at`, and `uuid` fields
- Basic query builder
- Transaction support

## Installation

Include the `SQ.php` file in your project and instantiate the `SQ` class with the path to your SQLite database file.

## Usage

### SQ Class

#### Creating a new record

```php
$sq = new SQ('path/to/database.sqlite');

// Create a new user
$user = $sq->dispense('user');
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->save();

// Automatically assigned fields:
// - uuid: A unique identifier
// - created_at: Timestamp of creation
// - updated_at: Timestamp of creation (same as created_at initially)
```

### Finding records

```php
// Find all users with the name 'John Doe'
$users = $sq->find('user', ['name' => 'John Doe']);
foreach ($users as $user) {
    echo "Found user: {$user->name}, {$user->email}, UUID: {$user->uuid}\n";
}

// Find a single user by criteria
$user = $sq->findOne('user', ['email' => 'john@example.com']);
if ($user) {
    echo "Found user: {$user->name}, {$user->email}, UUID: {$user->uuid}\n";
}
```

### Using the query builder

```php
// Using the query builder to find posts
$posts = $sq->query('post')
    ->where('user_id', '=', $user->id)
    ->orderBy('id', 'DESC')
    ->limit(10)
    ->get();
foreach ($posts as $post) {
    echo "Post: {$post->title} => {$post->content}, UUID: {$post->uuid}\n";
}
```

### Transactions

```php
$sq->beginTransaction();
try {
    // Perform multiple operations
    $user = $sq->dispense('user');
    $user->name = 'Jane Doe';
    $user->email = 'jane@example.com';
    $user->save();

    $post = $sq->dispense('post');
    $post->title = 'Hello World';
    $post->user_id = $user->id;
    $post->save();

    $sq->commit();
} catch (\Exception $e) {
    $sq->rollBack();
    throw $e;
}
```

### RT Class

#### Creating a new RT instance

```php
use JanOelze\Utils\RT;

$config = ['default_view' => 'home'];
$rt = new RT($config);
```

#### Adding Middleware

```php
$rt->addMiddleware(function($request, $response, $next) {
    // Middleware logic
    // Example: Logging
    error_log('Request received for view: ' . $request->getQuery('view'));
    return $next($request, $response);
});
```

#### Adding a View

```php
$rt->addView('GET', 'home', function($request, $response) {
    return [
        'title' => 'Home Page',
        'message' => 'Welcome to the Home Page!'
    ];
});
```

#### Running the RT Application

```php
$data = $rt->run();

if ($data['is_json'] ?? false) {
    header('Content-Type: application/json');
    echo json_encode($data);
} else {
    // Render HTML view with $data
}
```

## Interface Documentation

### SQ Class

- **__construct(string $path)**
  - Initializes the ORM with the specified SQLite database file.
  
- **dispense(string $type): SQBean**
  - Creates a new bean of the specified type.
  
- **find(string $type, array $criteria = []): array**
  - Retrieves records matching the criteria.
  
- **findOne(string $type, array $criteria = []): ?SQBean**
  - Retrieves a single record matching the criteria.
  
- **execute(string $sql, array $params = []): array**
  - Executes a raw SQL query.
  
- **query(string $table): SQQueryBuilder**
  - Returns a query builder for the specified table.
  
- **beginTransaction()**
  - Begins a database transaction.
  
- **commit()**
  - Commits the current transaction.
  
- **rollBack()**
  - Rolls back the current transaction.
  
- **getPDO(): PDO**
  - Returns the underlying PDO instance.

### RT Class

- **__construct(array $config = [])**
  - Initializes the RT application with optional configuration.
  
- **addMiddleware(callable $middleware)**
  - Adds a middleware function to the middleware stack.
  
- **addView(string $method, string $view, callable $handler)**
  - Registers a view handler for a specific HTTP method and view name.
  
- **run(): array**
  - Executes the middleware stack and returns the response data.

### Request Class

- **getQuery(string $key, mixed $default = null): mixed**
  - Retrieves a query parameter from the URL.
  
- **getMethod(): string**
  - Retrieves the HTTP method of the request.
  
- **getBody(): mixed**
  - Retrieves and decodes the request body.
  
- **setHeader(string $name, string $value): void**
  - Sets a header for the response.
  
- **getHeader(string $name): ?string**
  - Retrieves a specific request header.

### Response Class

- **// Currently empty, can be extended for response handling.**

## License

This project is licensed under the MIT License.
