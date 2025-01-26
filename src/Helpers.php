<?php

namespace JanOelze\Utils;

class Helpers
{
  public static function sanitize(string $input): string
  {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
  }

  public static function debug($data): void
  {
    echo '<pre>' . print_r($data, true) . '</pre>';
  }
}
