HT - A simple HTTP client for making API requests.
Features:
  * GET, POST, PUT, DELETE requests
  * Headers and authentication support
  * JSON and form-data handling

TXT - Utilities for text manipulation and formatting.
Features:
  * Markdown to HTML conversion
  * Slug generation
  * String transformations (camelCase, snake_case)

IMG - Provides image processing utilities.
Features:
  * Resize, crop, and rotate images
  * Convert between formats (JPEG, PNG, WebP)
  * Apply filters (grayscale, blur)

AUTH - Handles authentication and sessions.
Features:
  * Login and logout handling
  * User session management
  * Role-based access control

QUEUE - A simple queue system for processing tasks.
Features:
  * Queue and process jobs asynchronously
  * Supports different backends (database, Redis)
  * Job prioritization and retries

```php

$qu = new Queue([
  'db' => './queue.db',
]);

// Add a job to the queue
$qu->add('send_email', [
  'to' => 'user@email.com',
  'subject' => 'Hello!',
  'body' => 'This is a test email.',
]);

// Process jobs in the queue
$qu->registerHandler('send_email', function($job) {
  $to = $job['data']['to'];
  $subject = $job['data']['subject'];
  $body = $job['data']['body'];
  // Send email
  send_email($to, $subject, $body);
});