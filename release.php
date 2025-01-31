<?php

// get version number from composer.json
$composer = json_decode(file_get_contents('composer.json'));
$version = $composer->version ?? '0.1.0';

if (empty($version)) {
  echo 'Version number not found in composer.json';
  exit(1);
}

$new_version = readline("Enter new version number (current version is $version): ");

if (empty($new_version)) {
  echo 'Version number cannot be empty';
  exit(1);
}

if ($new_version === $version) {
  echo 'Version number is the same as the current version';
  exit(1);
}

$composer->version = $new_version;

file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT));

system("git add .");
system("git commit -m 'Release v$new_version'");
system("git tag -a v$new_version -m 'Release v$new_version'");
system("git push origin");
system("git push origin v$new_version");

echo "Version $new_version released successfully\n";
