<?php

require_once __DIR__ . '/../src/AI.php';

// AI is a simple library to interface with various LLMs
use JanOelze\Utils\AI;

// Get an OpenAI API key from the environment
$api_key = getenv('OPENAI_API_KEY') ?: 'your-api-key';

// Create a new instance of AI, it'll use the OpenAI API
$ai = new AI([
  'platform' => [
    'name' => 'openai',
    'api_key' => $api_key,
    'model' => 'gpt-4o-mini'
  ]
]);

// In the background, the platforms are implemented as separate handlers
// The OpenAI handler is the only one available at the moment

// supply a system prompt to the AI
$res = $ai->generate(['What is the meaning of life?']);

// check for errors
if ($res['error']) {
  echo 'Error: ' . $res['error'];
  exit;
}

// print the response
echo $res['response'];

// Optionally provide system and user prompts
$res = $ai->generate([
  "You're a helpful chatbot", // System prompt
  "Hello, how are you?" // User prompt
]);

// check for errors
if ($res['error']) {
  echo 'Error: ' . $res['error'];
  exit;
}

// print the response
echo $res['response'];

// Params can be passed as an array to the generate method
$res = $ai->generate([
  "You're a helpful chatbot", // System prompt
  "Hello, how are you?" // User prompt
], [
  'model' => 'gpt-4o', // override the model for this request
  'temperature' => 0.5,
]);

// check for errors
if ($res['error']) {
  echo 'Error: ' . $res['error'];
  exit;
}

// print the response
echo $res['response'];

// The AI class also features a prompt builder, it works like this:

// Create a new prompt builder
$prompt = $ai->prompt();

// Add a system message line
$prompt->addSystemMessage("Today's secret code is :code", ['code' => rand(1000, 9999)]);

// Add a second system message line
$prompt->addSystemMessage("Your task is to answer user questions.");

// Add a user message
$prompt->addUserMessage("What is the secret code?");

// For debugging, you can print the prompt
var_dump($prompt->get());
// Results in: [
//   "Today is the 19th of May, 2020.\r\nYour task is to answer user questions."
//   "What day is it today?"
// ]

// Generate the response
$res = $ai->generate($prompt->get());

// check for errors
if ($res['error']) {
  echo 'Error: ' . $res['error'];
  exit;
}

// print the response
echo $res['response'];


// Reference CURL implementation, this is how the class works in the background:

// function getAPIResponse($prompt, $endpoint, $api_key)
// {
//   // Initialisiere die cURL-Sitzung
//   $ch = curl_init($endpoint);

//   // Setze cURL-Optionen
//   curl_setopt($ch, CURLOPT_RETURNTRANSFER,
//     true
//   );
//   curl_setopt($ch, CURLOPT_POST, true);
//   curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//     "Authorization: Bearer $api_key",
//     "Content-Type: application/json"
//   ));

//   // Nutzlast für den Chat-Eingang unter Verwendung der Nachrichtenstruktur
//   $data = array(
//     'model' => 'gpt-4o-mini',
//     'messages' => array(
//       array('role' => 'system', 'content' => 'Sie sind ein hilfreicher Assistent.'),
//       array('role' => 'user', 'content' => $prompt)
//     )
//   );
//   $data_string = json_encode($data);

//   curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

//   // Führe die cURL-Sitzung aus
//   $response = curl_exec($ch);

//   // Überprüfe auf cURL-Fehler
//   if (curl_errno($ch)) {
//     echo 'Curl-Fehler: ' . curl_error($ch);
//     exit;
//   }

//   // Dekodiere die Antwort
//   $response_data = json_decode($response, true);

//   // Überprüfe, ob der Schlüssel 'choices' in der Antwort vorhanden ist und ob der Nachrichteninhalt vorhanden ist
//   if (!isset($response_data['choices']) || !isset($response_data['choices'][0]['message']['content'])) {
//     echo "Fehler: Unerwartete API-Antwort.<br>";
//     echo "Vollständige Antwort:<br>";
//     print_r($response_data);
//     exit;
//   }

//   // Hole die Antwort aus der API-Antwort
//   $answer = $response_data['choices'][0]['message']['content'];

//   // Schließe die cURL-Sitzung
//   curl_close($ch);

//   return $answer;
// }