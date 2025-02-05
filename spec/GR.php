<?php
require_once __DIR__ . '/../src/GR.php';

use JanOelze\Utils\GR;

// Initialize the GR library
$gr = new GR();

$data = [];

for ($i = 0; $i < 100; $i++) {
  $data[] = [$i, rand(0, 10000)];
}

// Create a simple sparkline chart
$gr->plot([
  'type'      => 'line',
  'animate'   => 1000, // Animate the line drawing (in milliseconds)
  'style'     => [
    'container' => [
      'width'         => 300,
      'height'        => 50,
    ],
  ],
  'datasets' => [
    [
      'values' => $data
    ]
  ],
]);

// Save the generated SVG to a file
$gr->save('./static/sparkline.svg');
exec('open -a "Google Chrome" ./static/sparkline.svg');

// $gr->plot([
//   // 'title'     => 'Sales Report 2023',
//   'type'      => 'line',
//   'smoothing' => 0.1,
//   'style'     => [
//     'container' => [
//       'width'         => 300,
//       'height'        => 50,
//       // 'padding-top'   => 2,
//       // 'padding-right' => 2,
//       // 'padding-bottom' => 2,
//       // 'padding-left'  => 2,
//     ],
//     // 'axis' => [
//     //   'stroke'       => 'gray',
//     //   'stroke-width' => 0,
//     // ],
//     // 'x-grid' => [
//     //   'stroke'       => '#e0e0e0',
//     //   'stroke-width' => 1,
//     // ],
//     // 'y-grid' => [
//     //   'stroke'       => '#e0e0e0',
//     //   'stroke-width' => 1,
//     // ],
//     // 'line' => [
//     //   'stroke'       => 'blue',
//     //   'stroke-width' => 2,
//     // ],
//   ],
//   'datasets' => [
//     [
//       // 'title'  => 'Product A',
//       'values' => $data,
//     ]
//   ],
// ]);

// // Output the generated SVG.
// $gr->save('./static/line-chart.svg');

// Open in chrome


// $padding = 5;

// // --- Example 1: Line Chart ---
// $gr = new GR();

// $gr->plot([
//   'title'    => 'Line Chart Example',
//   'type'     => 'line',
//   'smoothing' => 0.05,
//   'style'    => [
//     'container' => [
//       'width'         => 800,
//       'height'        => 400,
//       'padding-top'   => $padding + 40,
//       'padding-right' => $padding,
//       'padding-bottom' => $padding,
//       'padding-left'  => $padding,
//     ],
//     'axis'   => [
//       'stroke'       => 'black',
//       'stroke-width' => 1,
//     ],
//     'line'   => [
//       'stroke'       => 'blue',
//       'stroke-width' => 2,
//     ],
//     'x-grid' => [
//       'stroke'       => '#f0f0f0',
//       'stroke-width' => 1,
//     ],
//     'y-grid' => [
//       'stroke'       => '#f0f0f0',
//       'stroke-width' => 1,
//     ],
//   ],
//   'datasets' => [
//     [
//       'title'  => 'Dataset 1',
//       'color'  => 'red',
//       'x'      => 'X Axis',
//       'y'      => 'Y Axis',
//       'values' => [
//         [0, 5],
//         [1, 7],
//         [2, 4],
//         [3, 8],
//         [4, 6],
//       ],
//     ],
//     [
//       'title'  => 'Dataset 2',
//       'style'  => [
//         'stroke'       => 'green',
//         'stroke-width' => 3,
//       ],
//       'x'      => 'X Axis',
//       'y'      => 'Y Axis',
//       'values' => [
//         [0, 3],
//         [1, 4],
//         [2, 9],
//         [3, 2],
//         [4, 7],
//       ],
//     ],
//   ],
// ]);

// // Output the line chart SVG.
// $gr->save('./static/line-chart.svg');

// // --- Example 2: Bar Chart ---
// // To see a bar chart, comment out the above line chart block and uncomment this block:
// $gr = new GR();

// $gr->plot([
//     'title'    => 'Bar Chart Example',
//     'type'     => 'bar',
//     'style'    => [
//         'container' => [
//             'width'         => 800,
//             'height'        => 400,
//             'padding-top'   => $padding + 40,
//             'padding-right' => $padding,
//             'padding-bottom'=> $padding,
//             'padding-left'  => $padding,
//         ],
//         'axis'   => [
//             'stroke'       => 'black',
//             'stroke-width' => 1,
//         ],
//         'x-grid' => [
//             'stroke'       => '#f0f0f0',
//             'stroke-width' => 1,
//         ],
//         'y-grid' => [
//             'stroke'       => '#f0f0f0',
//             'stroke-width' => 1,
//         ],
//     ],
//     'datasets' => [
//         [
//             'title'  => 'Dataset 1',
//             'color'  => 'orange',
//             'values' => [
//                 [0, 5],
//                 [1, 7],
//                 [2, 4],
//                 [3, 8],
//                 [4, 6],
//             ],
//         ],
//         [
//             'title'  => 'Dataset 2',
//             'color'  => 'purple',
//             'values' => [
//                 [0, 3],
//                 [1, 4],
//                 [2, 9],
//                 [3, 2],
//                 [4, 7],
//             ],
//         ],
//     ],
// ]);

// // Output the bar chart SVG.
// echo $gr->output();

// $gr->save('./static/bar-chart.svg');
