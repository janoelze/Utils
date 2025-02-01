<?php

require_once __DIR__ . '/../src/ENV.php';

// ENV is a library to interface with environment variables and .env files
use JanOelze\Utils\ENV;

// Create a new instance of ENV, it'll read environment variables from a .env file
// Additionally, global environment variables are parsed too and made available, but overriden by the .env file
$env = new ENV(__DIR__ . '/.env');

// Set an environment variable
$env->set('OPENAI_API_KEY', 'key-12345678910');

// Get all environment variables
// print_r($env->get());
// => [
//      'PWD' => '/path/to/project',
//      'HOME' => '/path/to/home',
//      'USER' => 'username',
//      …
//      'OPENAI_API_KEY' => 'your-api
//    ]

// Get a specific environment variable
print_r($env->get('OPENAI_API_KEY'));
// => key-12345678910

// Get a specific environment variable
print_r($env->get('foo'));
// => bar