<?php

namespace JanOelze\Utils;

class VLD
{
  /**
   * @var array<string, callable>
   */
  protected $rules = [];

  public function __construct()
  {
    $this->initializeRules();
  }

  protected function initializeRules(): void
  {
    // Email validation using PHP's filter.
    $this->rules['email'] = function ($value) {
      return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    };

    // URL validation using PHP's filter.
    $this->rules['url'] = function ($value) {
      return filter_var($value, FILTER_VALIDATE_URL) !== false;
    };

    // IP address (any) validation.
    $this->rules['ip'] = function ($value) {
      return filter_var($value, FILTER_VALIDATE_IP) !== false;
    };

    // IPv4 address validation.
    $this->rules['ipv4'] = function ($value) {
      return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    };

    // IPv6 address validation.
    $this->rules['ipv6'] = function ($value) {
      return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    };

    // Domain validation.
    $this->rules['domain'] = function ($value) {
      return filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    };

    // Hostname validation (same as domain here).
    $this->rules['hostname'] = function ($value) {
      return filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    };

    // Alpha: only letters.
    $this->rules['alpha'] = function ($value) {
      return is_string($value) && ctype_alpha($value);
    };

    // AlphaNumeric: letters and numbers only.
    $this->rules['alphaNumeric'] = function ($value) {
      return is_string($value) && ctype_alnum($value);
    };

    // Numeric: any valid number.
    $this->rules['numeric'] = function ($value) {
      return is_numeric($value);
    };

    // Integer validation.
    $this->rules['integer'] = function ($value) {
      return filter_var($value, FILTER_VALIDATE_INT) !== false;
    };

    // Float validation.
    $this->rules['float'] = function ($value) {
      return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    };

    // Boolean: accepts booleans, "true", "false", 0, 1, etc.
    $this->rules['boolean'] = function ($value) {
      $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
      return $filtered !== null;
    };

    // Hexadecimal: checks for valid hex characters.
    $this->rules['hex'] = function ($value) {
      return is_string($value) && preg_match('/^[0-9a-fA-F]+$/', $value);
    };

    // Base64: checks for valid base64 encoding.
    $this->rules['base64'] = function ($value) {
      if (!is_string($value)) {
        return false;
      }
      $decoded = base64_decode($value, true);
      if ($decoded === false) {
        return false;
      }
      // Ensure that re-encoding gives the original value.
      return base64_encode($decoded) === $value;
    };

    // JSON: verifies if a string is valid JSON.
    $this->rules['json'] = function ($value) {
      if (!is_string($value)) {
        return false;
      }
      json_decode($value);
      return json_last_error() === JSON_ERROR_NONE;
    };

    // Date: expects format "YYYY-MM-DD".
    $this->rules['date'] = function ($value) {
      if (!is_string($value)) {
        return false;
      }
      $d = \DateTime::createFromFormat('Y-m-d', $value);
      return $d && $d->format('Y-m-d') === $value;
    };

    // Time: expects format "HH:MM:SS".
    $this->rules['time'] = function ($value) {
      if (!is_string($value)) {
        return false;
      }
      $d = \DateTime::createFromFormat('H:i:s', $value);
      return $d && $d->format('H:i:s') === $value;
    };

    // DateTime: expects format "YYYY-MM-DD HH:MM:SS".
    $this->rules['dateTime'] = function ($value) {
      if (!is_string($value)) {
        return false;
      }
      $d = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
      return $d && $d->format('Y-m-d H:i:s') === $value;
    };

    // Credit Card: validates using the Luhn algorithm.
    $this->rules['creditCard'] = function ($value) {
      if (!is_string($value)) {
        return false;
      }
      $number = preg_replace('/\D/', '', $value);
      $sum = 0;
      $numDigits = strlen($number);
      $parity = $numDigits % 2;
      for ($i = 0; $i < $numDigits; $i++) {
        $digit = (int)$number[$i];
        if ($i % 2 === $parity) {
          $digit *= 2;
          if ($digit > 9) {
            $digit -= 9;
          }
        }
        $sum += $digit;
      }
      return ($sum % 10) === 0;
    };

    // UUID: matches standard UUID formats.
    $this->rules['uuid'] = function ($value) {
      if (!is_string($value)) {
        return false;
      }
      return preg_match('/^\{?[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}\}?$/', $value);
    };

    // MAC Address: validates MAC addresses.
    $this->rules['macAddress'] = function ($value) {
      if (!is_string($value)) {
        return false;
      }
      return preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $value);
    };

    // MD5: expects 32 hexadecimal characters.
    $this->rules['md5'] = function ($value) {
      if (!is_string($value)) {
        return false;
      }
      return preg_match('/^[0-9a-fA-F]{32}$/', $value);
    };

    // SHA1: expects 40 hexadecimal characters.
    $this->rules['sha1'] = function ($value) {
      if (!is_string($value)) {
        return false;
      }
      return preg_match('/^[0-9a-fA-F]{40}$/', $value);
    };

    // SHA256: expects 64 hexadecimal characters.
    $this->rules['sha256'] = function ($value) {
      if (!is_string($value)) {
        return false;
      }
      return preg_match('/^[0-9a-fA-F]{64}$/', $value);
    };

    // SHA512: expects 128 hexadecimal characters.
    $this->rules['sha512'] = function ($value) {
      if (!is_string($value)) {
        return false;
      }
      return preg_match('/^[0-9a-fA-F]{128}$/', $value);
    };

    // ISBN: validates both ISBN-10 and ISBN-13 formats.
    $this->rules['isbn'] = function ($value) {
      // Remove spaces and hyphens.
      $value = str_replace([' ', '-'], '', $value);
      if (strlen($value) === 10) {
        // ISBN-10: must match 9 digits followed by a digit or "X".
        if (!preg_match('/^\d{9}[\dXx]$/', $value)) {
          return false;
        }
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
          $sum += ((int)$value[$i]) * ($i + 1);
        }
        $checksum = strtoupper($value[9]) === 'X' ? 10 : (int)$value[9];
        $sum += $checksum * 10;
        return $sum % 11 === 0;
      } elseif (strlen($value) === 13) {
        // ISBN-13: must be all digits.
        if (!preg_match('/^\d{13}$/', $value)) {
          return false;
        }
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
          $digit = (int)$value[$i];
          $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }
        $checksum = (10 - ($sum % 10)) % 10;
        return $checksum === (int)$value[12];
      }
      return false;
    };

    // ISSN: validates the International Standard Serial Number.
    $this->rules['issn'] = function ($value) {
      // Remove hyphens.
      $value = str_replace('-', '', $value);
      if (strlen($value) !== 8) {
        return false;
      }
      if (!preg_match('/^\d{7}[\dXx]$/', $value)) {
        return false;
      }
      $sum = 0;
      for ($i = 0; $i < 7; $i++) {
        $sum += ((int)$value[$i]) * (8 - $i);
      }
      $remainder = $sum % 11;
      $check = (11 - $remainder) % 11;
      $checkDigit = ($check === 10) ? 'X' : (string)$check;
      return strtoupper($value[7]) === $checkDigit;
    };
  }

  /**
   * Adds a custom validation rule.
   *
   * @param string   $ruleName The name of the rule.
   * @param callable $callable The function to validate the value.
   */
  public function addRule(string $ruleName, callable $callable): void
  {
    $this->rules[$ruleName] = $callable;
  }

  /**
   * Validates a value against a specific rule.
   *
   * @param string $ruleName The rule to apply.
   * @param mixed  $value    The value to validate.
   *
   * @return bool True if valid, false otherwise.
   *
   * @throws \InvalidArgumentException If the rule is not defined.
   */
  public function isValid(string $ruleName, $value): bool
  {
    if (!isset($this->rules[$ruleName])) {
      throw new \InvalidArgumentException("Rule '{$ruleName}' not found.");
    }
    $result = call_user_func($this->rules[$ruleName], $value);
    return (bool)$result;
  }
}
