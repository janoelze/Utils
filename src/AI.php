<?php

namespace JanOelze\Utils;

/**
 * Interface for provider classes.
 */
interface ProviderInterface
{
  /**
   * Generates a response from the provider.
   *
   * @param array $formattedMessages Array of messages (each an array with keys 'role' and 'content').
   * @param array $params Additional parameters to override provider defaults (like model, temperature, etc).
   * @return array An array with keys 'response' and 'error'. In case of success, 'error' is null.
   */
  public function generate(array $formattedMessages, array $params = []): array;
}

/**
 * The OpenAI provider.
 */
class OpenAIProvider implements ProviderInterface
{
  protected $apiKey;
  protected $model;
  protected $temperature;

  /**
   * OpenAIProvider constructor.
   *
   * @param array $config Configuration options. Expects 'api_key' and optionally 'model' and 'temperature'.
   * @throws \InvalidArgumentException
   */
  public function __construct(array $config)
  {
    if (empty($config['api_key'])) {
      throw new \InvalidArgumentException("API key is required for OpenAI.");
    }
    $this->apiKey      = $config['api_key'];
    $this->model       = $config['model'] ?? 'gpt-3.5-turbo';
    $this->temperature = $config['temperature'] ?? 1.0;
  }

  /**
   * Calls the OpenAI API.
   *
   * @param array $formattedMessages
   * @param array $params
   * @return array
   */
  public function generate(array $formattedMessages, array $params = []): array
  {
    // Merge provider defaults with any override parameters.
    $model = $params['model'] ?? $this->model;
    $temperature = $params['temperature'] ?? $this->temperature;

    $data = [
      'model'       => $model,
      'messages'    => $formattedMessages,
      'temperature' => $temperature,
    ];
    $endpoint = "https://api.openai.com/v1/chat/completions";

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      "Authorization: Bearer " . $this->apiKey,
      "Content-Type: application/json"
    ]);

    $dataString = json_encode($data);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
      $error = 'Curl error: ' . curl_error($ch);
      curl_close($ch);
      return [
        'response' => null,
        'error'    => $error,
      ];
    }
    curl_close($ch);

    $responseData = json_decode($response, true);
    if (!isset($responseData['choices'][0]['message']['content'])) {
      return [
        'response' => null,
        'error'    => "Unexpected API response: " . print_r($responseData, true),
      ];
    }
    $answer = $responseData['choices'][0]['message']['content'];

    return [
      'response' => $answer,
      'error'    => null,
    ];
  }
}

/**
 * The main AI class that uses a provider to generate responses.
 */
class AI
{
  /**
   * @var ProviderInterface
   */
  protected $provider;

  /**
   * AI constructor.
   *
   * @param array $config Expects a 'platform' key with provider details.
   * @throws \InvalidArgumentException
   */
  public function __construct(array $config = [])
  {
    if (!isset($config['platform'])) {
      throw new \InvalidArgumentException('Platform configuration is required.');
    }
    $platform = $config['platform'];
    $name = strtolower($platform['name']);

    // Based on the platform name, instantiate the proper provider.
    switch ($name) {
      case 'openai':
        $this->provider = new OpenAIProvider($platform);
        break;
      default:
        throw new \InvalidArgumentException("Unsupported platform: " . $platform['name']);
    }
  }

  /**
   * Generates a response using the configured provider.
   *
   * The $prompt argument can be:
   * - An array of strings. If a single element is given it is assumed to be a user message.
   *   If more than one element is passed, the first is assumed to be a system prompt and the rest are user messages.
   * - An object with a get() method (like our prompt builder).
   *
   * Additional API parameters (like model, temperature, etc.) can be passed in $params.
   *
   * @param mixed $prompt
   * @param array $params
   * @return array
   */
  public function generate($prompt, array $params = []): array
  {
    // If $prompt is an object that implements get(), then use that.
    if (is_object($prompt) && method_exists($prompt, 'get')) {
      $prompt = $prompt->get();
    }

    // Ensure $prompt is an array.
    if (!is_array($prompt)) {
      $prompt = [$prompt];
    }

    // Format the messages. If they are not already formatted (i.e. arrays with a 'role' key)
    // we assume that if there is more than one element, the first is a system message and the rest are user messages.
    $formattedMessages = [];
    if (isset($prompt[0]) && is_array($prompt[0]) && isset($prompt[0]['role'])) {
      // Already formatted.
      $formattedMessages = $prompt;
    } else {
      if (count($prompt) === 1) {
        // Only one message: assume it's a user message.
        $formattedMessages[] = [
          'role'    => 'user',
          'content' => $prompt[0]
        ];
      } elseif (count($prompt) > 1) {
        // Multiple messages: first is system, rest are user messages.
        $formattedMessages[] = [
          'role'    => 'system',
          'content' => $prompt[0]
        ];
        for ($i = 1, $len = count($prompt); $i < $len; $i++) {
          $formattedMessages[] = [
            'role'    => 'user',
            'content' => $prompt[$i]
          ];
        }
      }
    }

    // Delegate the request to the provider.
    return $this->provider->generate($formattedMessages, $params);
  }

  /**
   * Returns a new prompt builder instance.
   *
   * @return Prompt
   */
  public function prompt(): Prompt
  {
    return new Prompt();
  }
}

/**
 * A simple prompt builder for constructing multi-line prompts.
 *
 * You can add one or more system messages (which are later combined)
 * and one or more user messages.
 */
class Prompt
{
  protected $systemMessages = [];
  protected $userMessages   = [];

  /**
   * Adds a system message. Placeholders in the message (e.g. ":day") will be replaced
   * with the values provided in $params.
   *
   * @param string $message
   * @param array  $params
   * @return void
   */
  public function addSystemMessage(string $message, array $params = []): void
  {
    if (!empty($params)) {
      foreach ($params as $key => $value) {
        $message = str_replace(':' . $key, $value, $message);
      }
    }
    $this->systemMessages[] = $message;
  }

  /**
   * Adds a user message. Placeholders in the message will be replaced using $params.
   *
   * @param string $message
   * @param array  $params
   * @return void
   */
  public function addUserMessage(string $message, array $params = []): void
  {
    if (!empty($params)) {
      foreach ($params as $key => $value) {
        $message = str_replace(':' . $key, $value, $message);
      }
    }
    $this->userMessages[] = $message;
  }

  /**
   * Returns the constructed prompt as an array.
   *
   * The system messages are combined (using "\r\n" as a separator) and placed
   * as the first element, while each user message becomes a separate element.
   *
   * @return array
   */
  public function get(): array
  {
    $messages = [];
    if (!empty($this->systemMessages)) {
      // Combine all system messages into one string.
      $messages[] = implode("\r\n", $this->systemMessages);
    }
    // Append each user message.
    foreach ($this->userMessages as $msg) {
      $messages[] = $msg;
    }
    return $messages;
  }

  /**
   * Magic method so that you can easily echo the prompt for debugging.
   *
   * @return string
   */
  public function __toString(): string
  {
    return print_r($this->get(), true);
  }
}
