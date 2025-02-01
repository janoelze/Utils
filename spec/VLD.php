<?php

require_once __DIR__ . '/../../src/VLD.php';

use JanOelze\Utils\VLD;

// VLD is a simple library that can be used to validate various types of data.

$vld = new VLD();

// You can also use the built-in rules to validate data.
$vld->isValid('email', 'alice@gmail.com'); // true
$vld->isValid('email', 'alice'); // false

// You can also use the built-in rules to validate data.
$vld->isValid('url', 'http://example.com'); // true
$vld->isValid('url', 'example.com'); // false

// The built-in rules are:
// - email, url, ip, ipv4, ipv6, domain, hostname, alpha, alphaNumeric,
//   numeric, integer, float, boolean, hex, base64, json, date, time, dateTime,
//   creditCard, uuid, macAddress, md5, sha1, sha256, sha512, isbn, issn,
//   required, notEmpty, minLength, maxLength, min, max, slug

// Add a custom rule to validate license plates in the format "AAA-1234".
$vld->addRule('license-plate', function ($value) {
  return preg_match('/^[A-Z]{1,3}-[0-9]{1,4}$/', $value);
});

// And now you can use the new rule to validate license plates.
$vld->isValid('license-plate', 'ABC-1234'); // true