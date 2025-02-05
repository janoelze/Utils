<?php
require_once __DIR__ . '/../src/GR.php';

use JanOelze\Utils\GR;

$padding = 5;

// --- Example 1: Line Chart ---
$gr = new GR();

$gr->plot([
  'title'    => 'Line Chart Example',
  'type'     => 'line',
  'smoothing' => 0.05,
  'style'    => [
    'container' => [
      'width'         => 800,
      'height'        => 400,
      'padding-top'   => $padding + 40,
      'padding-right' => $padding,
      'padding-bottom' => $padding,
      'padding-left'  => $padding,
    ],
    'axis'   => [
      'stroke'       => 'black',
      'stroke-width' => 1,
    ],
    'line'   => [
      'stroke'       => 'blue',
      'stroke-width' => 2,
    ],
    'x-grid' => [
      'stroke'       => '#f0f0f0',
      'stroke-width' => 1,
    ],
    'y-grid' => [
      'stroke'       => '#f0f0f0',
      'stroke-width' => 1,
    ],
  ],
  'datasets' => [
    [
      'title'  => 'Dataset 1',
      'color'  => 'red',
      'x'      => 'X Axis',
      'y'      => 'Y Axis',
      'values' => [
        [0, 5],
        [1, 7],
        [2, 4],
        [3, 8],
        [4, 6],
      ],
    ],
    [
      'title'  => 'Dataset 2',
      'style'  => [
        'stroke'       => 'green',
        'stroke-width' => 3,
      ],
      'x'      => 'X Axis',
      'y'      => 'Y Axis',
      'values' => [
        [0, 3],
        [1, 4],
        [2, 9],
        [3, 2],
        [4, 7],
      ],
    ],
  ],
]);

// Output the line chart SVG.
// echo $gr->output();

// --- Example 2: Bar Chart ---
// To see a bar chart, comment out the above line chart block and uncomment this block:
$gr = new GR();

$gr->plot([
    'title'    => 'Bar Chart Example',
    'type'     => 'bar',
    'style'    => [
        'container' => [
            'width'         => 800,
            'height'        => 400,
            'padding-top'   => $padding + 40,
            'padding-right' => $padding,
            'padding-bottom'=> $padding,
            'padding-left'  => $padding,
        ],
        'axis'   => [
            'stroke'       => 'black',
            'stroke-width' => 1,
        ],
        'x-grid' => [
            'stroke'       => '#f0f0f0',
            'stroke-width' => 1,
        ],
        'y-grid' => [
            'stroke'       => '#f0f0f0',
            'stroke-width' => 1,
        ],
    ],
    'datasets' => [
        [
            'title'  => 'Dataset 1',
            'color'  => 'orange',
            'values' => [
                [0, 5],
                [1, 7],
                [2, 4],
                [3, 8],
                [4, 6],
            ],
        ],
        [
            'title'  => 'Dataset 2',
            'color'  => 'purple',
            'values' => [
                [0, 3],
                [1, 4],
                [2, 9],
                [3, 2],
                [4, 7],
            ],
        ],
    ],
]);

// Output the bar chart SVG.
echo $gr->output();

// Optionally, you can save the chart to a file instead of or in addition to outputting it:
$gr->save('/tmp/chart.svg');

// Open in Chrome:
exec('open -a "Google Chrome" /tmp/chart.svg');
