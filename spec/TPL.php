<?php

require_once __DIR__ . '/../../src/TPL.php';

use JanOelze\Utils\TPL;

$tpl = new TPL();

$tpl->addLayout('global', function ($content) {
  $html = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
      <meta charset='UTF-8'>
      <meta name='viewport' content='width=device-width, initial-scale=1.0'>
      <title>My Contacts</title>
    </head>
    <body>
      $content
    </body>
    </html>
  ";
  return $html;
});

$tpl->addPartial('button', function ($data) use ($tpl) {
  $text = $data['text'];
  $url = $data['url'];
  return '<a href="' . $url . '">' . $text . '</a>';
});

$tpl->addPartial('contacts-list', function ($data) use ($tpl) {
  $contacts = $data['contacts'];
  $html = '<ul>';
  foreach ($contacts as $contact) {
    $html .= '<li>' . $tpl->render('contact', $contact) . '</li>';
  }
  $html .= '</ul>';
  return $html;
});

$contacts = [
  [
    'name' => 'Alice',
    'email' => ''
  ],
  [
    'name' => 'Bob',
    'email' => ''
  ],
  [
    'name' => 'Charlie',
    'email' => ''
  ],
];

$tpl->render('global', "
  <h1>My Contacts</h1>
  <p>Welcome to my contacts page. Here you can find a list of people I know.</p>
  <h2>Contacts</h2>
  " . $tpl->render('contacts-list', ['contacts' => $contacts]) . "
");
