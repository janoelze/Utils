<?php
session_start();

// You'd use vendor/autoload.php here in a real project.
require_once __DIR__ . '/../../src/RT.php';
require_once __DIR__ . '/../../src/AI.php';

use JanOelze\Utils\RT;
use JanOelze\Utils\AI;

// Set up the base URL dynamically.
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
  . "://{$_SERVER['HTTP_HOST']}" . strtok($_SERVER['REQUEST_URI'], '?');

$rt = new RT([
  'base_url'    => $baseUrl,
  'page_param'  => 'action',
  'default_page' => 'chat'
]);

// Initialize the conversation history if not already set.
if (!isset($_SESSION['conversation'])) {
  $_SESSION['conversation'] = [];
}

/**
 * GET /chat
 * Returns the HTML page.
 */
$rt->addPage('GET', 'chat', function () {
  // Simply return an empty array; the HTML below handles the rest.
  return [];
});

/**
 * GET /get-history
 * Returns the conversation history as JSON.
 */
$rt->addPage('GET', 'get-history', function () {
  header('Content-Type: application/json');
  echo json_encode(['conversation' => $_SESSION['conversation']]);
  exit;
});

/**
 * GET /reset
 * Clears the conversation history.
 */
$rt->addPage('GET', 'reset', function () {
  $_SESSION['conversation'] = [];
  header('Content-Type: application/json');
  echo json_encode(['conversation' => []]);
  exit;
});

/**
 * POST /send-message
 * Processes a new user message and returns the updated conversation.
 */
$rt->addPage('POST', 'send-message', function ($req) {
  header('Content-Type: application/json');

  $userMessage = trim($req->getPost('message'));
  if ($userMessage === '') {
    echo json_encode(['error' => 'Empty message', 'conversation' => $_SESSION['conversation']]);
    exit;
  }

  // Append the user's message.
  $_SESSION['conversation'][] = ['role' => 'user', 'message' => $userMessage];

  /*
     * Build a prompt using the conversation history.
     * Format:
     *   System: You are a helpful chatbot.
     *   User: Hello
     *   Bot: Hi there!
     *   User: How are you?
     *   Bot:
     */
  $promptLines = [];
  // Add a system instruction if starting conversation.
  if (empty($_SESSION['conversation']) || (count($_SESSION['conversation']) === 1 && $_SESSION['conversation'][0]['role'] === 'user')) {
    $promptLines[] = "System: You are a helpful chatbot.";
  }
  foreach ($_SESSION['conversation'] as $entry) {
    if ($entry['role'] === 'user') {
      $promptLines[] = "User: " . $entry['message'];
    } else {
      $promptLines[] = "Bot: " . $entry['message'];
    }
  }
  // Add the cue for the bot’s reply.
  $promptLines[] = "Bot:";
  $promptText = implode("\n", $promptLines);

  // Initialize the AI module.
  $ai = new AI([
    'platform' => [
      'name'    => 'openai',
      'api_key' => getenv('OPENAI_API_KEY'),
      'model'   => 'gpt-4'
    ]
  ]);
  $response = $ai->generate([$promptText], ['temperature' => 0.7]);

  if (isset($response['error'])) {
    $botMessage = "Error: " . $response['error'];
  } else {
    $botMessage = trim($response['response']);
  }

  // Append the bot's reply.
  $_SESSION['conversation'][] = ['role' => 'bot', 'message' => $botMessage];

  // Return the updated conversation.
  echo json_encode(['conversation' => $_SESSION['conversation']]);
  exit;
});

// Run the router.
$data = $rt->run();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Chatbot Interface</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 20px;
      background: #f9f9f9;
    }

    .chat-container {
      max-width: 600px;
      margin: auto;
      background: #fff;
      padding: 20px;
      box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    }

    .chat-box {
      margin-bottom: 20px;
      height: 400px;
      overflow-y: auto;
      border: 1px solid #ddd;
      padding: 10px;
      background: #fafafa;
    }

    .message {
      margin: 10px 0;
    }

    .message.user {
      text-align: right;
    }

    .message.bot {
      text-align: left;
    }

    .message span {
      display: inline-block;
      padding: 10px;
      border-radius: 5px;
      max-width: 80%;
    }

    .user span {
      background-color: #DCF8C6;
    }

    .bot span {
      background-color: #F1F0F0;
    }

    form {
      display: flex;
    }

    input[type="text"] {
      flex: 1;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 3px;
    }

    button {
      padding: 10px 15px;
      margin-left: 5px;
      border: none;
      background: #007BFF;
      color: #fff;
      border-radius: 3px;
      cursor: pointer;
    }

    button:hover {
      background: #0056b3;
    }

    #reset-btn {
      margin-left: 5px;
      background: #DC3545;
    }

    #activity-indicator {
      text-align: center;
      font-style: italic;
      color: #555;
      margin: 10px 0;
    }
  </style>
</head>

<body>
  <div class="chat-container">
    <h1>Chatbot Interface</h1>
    <div id="chat-box" class="chat-box">
      <!-- Chat messages will be inserted here -->
    </div>
    <form id="chat-form">
      <input type="text" id="message" name="message" placeholder="Type your message..." required>
      <button type="submit" id="submit-btn">Send</button>
      <button type="button" id="reset-btn">Reset</button>
    </form>
  </div>
  <script>
    const chatBox = document.getElementById('chat-box');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message');
    const submitBtn = document.getElementById('submit-btn');
    const resetBtn = document.getElementById('reset-btn');

    // Helper function to render conversation
    function renderConversation(conversation) {
      chatBox.innerHTML = '';
      conversation.forEach(entry => {
        const div = document.createElement('div');
        div.classList.add('message', entry.role);
        const span = document.createElement('span');
        span.textContent = entry.message;
        div.appendChild(span);
        chatBox.appendChild(div);
      });
      // Scroll to the bottom
      chatBox.scrollTop = chatBox.scrollHeight;
    }

    // Fetch the current conversation history
    function fetchConversation() {
      fetch('<?= $rt->getUrl("get-history") ?>')
        .then(response => response.json())
        .then(data => {
          if (data.conversation) {
            renderConversation(data.conversation);
          }
        })
        .catch(err => console.error('Error fetching conversation:', err));
    }

    // Handle form submission via fetch()
    chatForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const message = messageInput.value.trim();
      if (!message) return;
      submitBtn.disabled = true;

      // Immediately update UI with user message and a temporary bot indicator.
      const userDiv = document.createElement('div');
      userDiv.classList.add('message', 'user');
      const userSpan = document.createElement('span');
      userSpan.textContent = message;
      userDiv.appendChild(userSpan);
      chatBox.appendChild(userDiv);

      const tempBotDiv = document.createElement('div');
      tempBotDiv.classList.add('message', 'bot');
      const tempBotSpan = document.createElement('span');
      tempBotSpan.textContent = 'Bot is thinking...';
      tempBotDiv.appendChild(tempBotSpan);
      chatBox.appendChild(tempBotDiv);
      chatBox.scrollTop = chatBox.scrollHeight;

      fetch('<?= $rt->getUrl("send-message") ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: new URLSearchParams({
            message
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.conversation) {
            renderConversation(data.conversation);
            messageInput.value = '';
            messageInput.focus();
            submitBtn.disabled = false;
          }
        })
        .catch(err => {
          console.error('Error sending message:', err);
          submitBtn.disabled = false;
        });
    });

    resetBtn.addEventListener('click', function() {
      if (!confirm('Reset conversation?')) return;
      fetch('<?= $rt->getUrl("reset") ?>')
        .then(response => response.json())
        .then(data => {
          renderConversation(data.conversation);
        })
        .catch(err => console.error('Error resetting conversation:', err));
    });

    // Poll for conversation updates every 3 seconds.
    setInterval(fetchConversation, 3000);

    // Initial load.
    fetchConversation();
  </script>
</body>

</html>