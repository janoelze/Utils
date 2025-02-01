<?php

require_once __DIR__ . '/../../src/RT.php';
require_once __DIR__ . '/../../src/SQ.php';

use JanOelze\Utils\RT;
use JanOelze\Utils\SQ;

$sq = new SQ('./db.sqlite');
$rt = new RT([
  'base_url'    => 'http://localhost:8001',
  'page_param'  => 'action',
  'default_page' => 'home',
]);

$rt->addPage('GET', 'home', function () use ($sq) {
  return [
    'title' => 'Todo List',
    'todos' => $sq->find('todo'),
  ];
});

$rt->addPage('POST', 'add-todo', function ($req) use ($rt, $sq) {
  $title = $req->getPost('title');
  if (!$title) $rt->redirect($rt->getUrl('home'));
  $todo = $sq->dispense('todo');
  $todo->title = $title;
  $todo->completed = false;
  $todo->save();
  $rt->redirect($rt->getUrl('home'));
});

$rt->addPage('POST', 'toggle-todo', function ($req) use ($rt, $sq) {
  $todo = $sq->findOne('todo', [
    'uuid' => $req->getPost('uuid') ?? null,
  ]);

  if ($todo) {
    $todo->completed = !$todo->completed;
    $todo->save();
  }

  $rt->redirect($rt->getUrl('home'));
});

$data = $rt->run();

?>
<!DOCTYPE html>
<html>

<head>
  <title><?= $data['title'] ?></title>
</head>

<body>
  <h1><?= $data['title'] ?></h1>
  <?php if (isset($data['todos'])): ?>
    <ul>
      <?php foreach ($data['todos'] as $todo): ?>
        <li>
          <form method="post" action="<?= $rt->getUrl('toggle-todo') ?>">
            <input type="hidden" name="uuid" value="<?= $todo->uuid ?>">
            <label>
              <input type="checkbox" name="completed"
                <?= $todo->completed ? 'checked' : '' ?>
                onchange="this.form.submit()">
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