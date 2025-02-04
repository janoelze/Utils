<?php

require_once __DIR__ . '/../src/UI.php';

// AI is a simple library to interface with various LLMs
use JanOelze\Utils\UI;
use JanOelze\Utils\UIElement;

$ui = new UI();

// Build the <head> section.
$head = $ui->head();
$head->add(
  $ui->meta(['charset' => 'UTF-8']),
  $ui->meta([
    'name'    => 'viewport',
    'content' => 'width=device-width, initial-scale=1.0'
  ]),
  $ui->title('My Page Title'),
  $ui->stylesheet(['href' => 'styles.css'])
);

// Build the <body> section.
$body = $ui->body();
$body->add(
  $ui->box(['direction' => 'column', 'class' => 'main-container'])->add(
    $ui->box(['class' => 'header'])->add($ui->link('/', 'Home')),
    $ui->box(['class' => 'content'])->add($ui->link('/about', 'About')),
    $ui->box(['class' => 'footer'])->add($ui->link('/contact', 'Contact'))
  )
);

// Output the complete HTML document.
echo $ui->document($head, $body);