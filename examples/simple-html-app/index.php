<?php

// Simple example of a todo list application using SQ and RT

require_once __DIR__ . '/../../src/RT.php';
require_once __DIR__ . '/../../src/SQ.php';

use JanOelze\Utils\RT;
use JanOelze\Utils\SQ;

// Initialize the database
$sq = new SQ('./db.sqlite');

// Initialize the router
$rt = new RT([
  'base_url' => 'http://localhost:8001',
  'page_param' => 'action',
  'default_page' => 'home',
]);

// Register a page that accepts GET requests to /?page=home (the default page)
$rt->addPage('GET', 'home', function () use ($sq) {
  return [
    'title' => 'Todo List',
    'todos' => $sq->query('todo')->get(),
  ];
});

// Register a page that accepts GET requests to /?page=todo&uuid=...
$rt->addPage('GET', 'todo', function ($req) use ($sq, $rt) {
  $uuid = $req->getQuery('uuid');
  $todos = $sq->query('todo')->where('uuid', '=', $uuid)->get();
  if (empty($todos)) {
    $rt->redirect($rt->getUrl('home'));
  }
  return [
    'title' => $todos[0]->title,
    'todo' => $todos[0],
  ];
});

// Register a page that accepts POST requests to /?page=add-todo
$rt->addPage('POST', 'add-todo', function ($req) use ($rt, $sq) {
  $title = $req->getPost('title');

  if (!$title) {
    $rt->redirect($rt->getUrl('home'));
  }

  $todo = $sq->dispense('todo');
  $todo->title = $title;
  $todo->completed = false;
  $todo->save();

  $rt->redirect($rt->getUrl('home'));
});

// Register a page that accepts POST requests to /?page=toggle-todo
$rt->addPage('POST', 'toggle-todo', function ($req) use ($rt, $sq) {
  $uuid = $req->getPost('uuid');
  $completed = $req->getPost('completed');

  $todos = $sq->query('todo')->where('uuid', '=', $uuid)->get();

  if (empty($todos)) {
    $rt->redirect($rt->getUrl('home'));
  }

  $todo = $todos[0];

  $todo->completed = $completed;
  $todo->save();

  $rt->redirect($rt->getUrl('home'));
});

// Dispatch the request
$data = $rt->run();

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title><?= $data['title'] ?></title>
</head>

<body>
  <h1><?= $data['title'] ?></h1>
  <?php if (isset($data['todos'])) : ?>
    <ul>
      <?php foreach ($data['todos'] as $todo) : ?>
        <li>
          <form method="post" action="<?= $rt->getUrl('toggle-todo') ?>">
            <input type="hidden" name="uuid" value="<?= $todo->uuid ?>">
            <label>
              <input type="checkbox" name="completed" <?= $todo->completed ? 'checked' : '' ?> onchange="this.form.submit()">
              <?= $todo->title ?>
            </label>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <form method="post" action="<?= $rt->getUrl('add-todo') ?>">
    <input type="text" name="title" placeholder="Enter a new todo">
    <button type="submit">Add Todo</button>
  </form>
</body>

</html>