<?php

require_once __DIR__ . '/../src/LG.php';

use JanOelze\Utils\LG;

$tempFile = tempnam(sys_get_temp_dir(), 'LGTest_');

// LG is a simple logging class that can be used to log messages to the console

// Create a new instance of LG, it'll log messages to the console and to a file called log.txt
$lg = new LG([
  'date_format' => 'd-m-Y H:i:s',
  'colors' => true,
  'destinations' => [
    'console', // Log to the console
    $tempFile  // Log to a file
  ]
]);

// $lg->log('Logging to console and', $tempFile);

// Log a plain message
$lg->log('This is a log message');
// => 19-05-2020 12:00:00 [LOG] This is a log message

// print() and write() are aliases for log()
$lg->print('This is a print message');
$lg->write('This is a write message');
// => 19-05-2020 12:00:00 [LOG] This is a print message
// => 19-05-2020 12:00:00 [LOG] This is a write message

// Log a warning message, it will be displayed in yellow
$lg->warn('This is a warning message');
// => 19-05-2020 12:00:00 [WRN] This is a warning message

// Log an error message, it will be displayed in red
$lg->error('This is an error message');
// => 19-05-2020 12:00:00 [ERR] This is an error message

// Log a success message, it will be displayed in green
$lg->success('This is a success message');
// => 19-05-2020 12:00:00 [SCS] This is a success message

// Log a debug message, it will be displayed in blue
$lg->debug('This is a debug message');
// => 19-05-2020 12:00:00 [DBG] This is a debug message

// Log a message with multiple arguments, they will be concatenated
$lg->log('Retried', 50, 'times');
// => 19-05-2020 12:00:00 [LOG] Retried 50 times

// Log an array, it will be pretty printed
$lg->log(['key' => 'value']);
// => 19-05-2020 12:00:00 [LOG] {
//      "key": "value"
//    }

class Dummy
{
  public $name = "Dummy Object";
  public $numbers = [1, 2, 3];
}

// Objects will be pretty printed as well
$lg->log(new Dummy());
// => 19-05-2020 12:00:00 [LOG] {
//      "name": "Dummy Object",
//      "numbers": [
//        1,
//        2,
//        3
//      ]
//    }