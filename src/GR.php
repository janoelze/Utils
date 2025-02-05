<?php

namespace JanOelze\Utils;

/**
 * Base chart type abstract class.
 */
abstract class ChartType
{
  /** @var array */
  protected $config;
  /** @var array */
  protected $dimensions; // Contains: width, height, paddingTop, paddingRight, paddingBottom, paddingLeft.
  /** @var float */
  protected $minX;
  /** @var float */
  protected $maxX;
  /** @var float */
  protected $minY;
  /** @var float */
  protected $maxY;

  /**
   * @param array $config The chart configuration (datasets and style)
   * @param array $dimensions Plot area dimensions and padding.
   * @param float $minX
   * @param float $maxX
   * @param float $minY
   * @param float $maxY
   */
  public function __construct(array $config, array $dimensions, float $minX, float $maxX, float $minY, float $maxY)
  {
    $this->config = $config;
    $this->dimensions = $dimensions;
    $this->minX = $minX;
    $this->maxX = $maxX;
    $this->minY = $minY;
    $this->maxY = $maxY;
  }

  /**
   * Render the chart-specific SVG elements (data visualization) and return them as a string.
   *
   * @return string
   */
  abstract public function render(): string;
}

/**
 * Renders a line chart.
 */
class LineChart extends ChartType
{
  /**
   * Optionally smooth curves if a smoothing factor is provided.
   *
   * @param array $points Array of points with keys 'x' and 'y'.
   * @param float $smoothing Smoothing factor.
   * @return string SVG path data.
   */
  private function getSmoothedPath(array $points, float $smoothing): string
  {
    $count = count($points);
    if ($count < 2) {
      return '';
    }
    $d = 'M ' . $points[0]['x'] . ' ' . $points[0]['y'] . ' ';
    for ($i = 0; $i < $count - 1; $i++) {
      $p0 = ($i === 0) ? $points[$i] : $points[$i - 1];
      $p1 = $points[$i];
      $p2 = $points[$i + 1];
      $p3 = ($i + 2 < $count) ? $points[$i + 2] : $p2;
      $cp1x = $p1['x'] + $smoothing * ($p2['x'] - $p0['x']);
      $cp1y = $p1['y'] + $smoothing * ($p2['y'] - $p0['y']);
      $cp2x = $p2['x'] - $smoothing * ($p3['x'] - $p1['x']);
      $cp2y = $p2['y'] - $smoothing * ($p3['y'] - $p1['y']);
      $d .= 'C ' . $cp1x . ' ' . $cp1y . ', ' . $cp2x . ' ' . $cp2y . ', ' . $p2['x'] . ' ' . $p2['y'] . ' ';
    }
    return $d;
  }

  /**
   * Render the line chart as SVG.
   *
   * @return string
   */
  public function render(): string
  {
    $svg = '';

    // Get plot area dimensions.
    $plotWidth  = $this->dimensions['width'] - $this->dimensions['paddingLeft'] - $this->dimensions['paddingRight'];
    $plotHeight = $this->dimensions['height'] - $this->dimensions['paddingTop'] - $this->dimensions['paddingBottom'];

    // Global default style for a simple sparkline.
    $globalStroke = 'black';
    $globalStrokeWidth = 1;
    $smoothing = 0.0;
    if (isset($this->config['style']['line']) && is_array($this->config['style']['line'])) {
      $globalStroke = $this->config['style']['line']['stroke'] ?? $globalStroke;
      $globalStrokeWidth = $this->config['style']['line']['stroke-width'] ?? $globalStrokeWidth;
    }
    if (isset($this->config['smoothing'])) {
      $smoothing = floatval($this->config['smoothing']);
    }
    // New: Convert animate value (ms) to seconds.
    $animateDuration = isset($this->config['animate']) ? floatval($this->config['animate']) / 1000 : 0;

    // Process each dataset.
    foreach ($this->config['datasets'] as $dataset) {
      if (!isset($dataset['values']) || count($dataset['values']) < 2) {
        continue;
      }
      // Allow dataset-specific overrides.
      $datasetStroke = $globalStroke;
      $datasetStrokeWidth = $globalStrokeWidth;
      if (isset($dataset['color'])) {
        $datasetStroke = $dataset['color'];
      }
      if (isset($dataset['style']) && is_array($dataset['style'])) {
        if (isset($dataset['style']['stroke'])) {
          $datasetStroke = $dataset['style']['stroke'];
        }
        if (isset($dataset['style']['stroke-width'])) {
          $datasetStrokeWidth = $dataset['style']['stroke-width'];
        }
      }
      // Transform data points to SVG coordinates.
      $points = [];
      foreach ($dataset['values'] as $point) {
        list($xVal, $yVal) = $point;
        $x = $this->dimensions['paddingLeft'] + (($xVal - $this->minX) / ($this->maxX - $this->minX)) * $plotWidth;
        $y = $this->dimensions['height'] - $this->dimensions['paddingBottom'] - (($yVal - $this->minY) / ($this->maxY - $this->minY)) * $plotHeight;
        $points[] = ['x' => $x, 'y' => $y];
      }
      // Render with smoothing if requested.
      if ($smoothing > 0) {
        $pathData = $this->getSmoothedPath($points, $smoothing);
        if ($animateDuration > 0) {
          $svg .= '<path d="' . $pathData . '" fill="none" stroke="' . $datasetStroke . '" stroke-width="' . $datasetStrokeWidth . '" pathLength="1000" stroke-dasharray="1000" stroke-dashoffset="1000">' . "\n";
          $svg .= '<animate attributeName="stroke-dashoffset" from="1000" to="0" dur="' . $animateDuration . 's" fill="freeze"/>' . "\n";
          $svg .= '</path>' . "\n";
        } else {
          $svg .= '<path d="' . $pathData . '" fill="none" stroke="' . $datasetStroke . '" stroke-width="' . $datasetStrokeWidth . '"/>' . "\n";
        }
      } else {
        $pts = [];
        foreach ($points as $pt) {
          $pts[] = $pt['x'] . ',' . $pt['y'];
        }
        if ($animateDuration > 0) {
          $svg .= '<polyline points="' . implode(' ', $pts) . '" fill="none" stroke="' . $datasetStroke . '" stroke-width="' . $datasetStrokeWidth . '" pathLength="1000" stroke-dasharray="1000" stroke-dashoffset="1000">' . "\n";
          $svg .= '<animate attributeName="stroke-dashoffset" from="1000" to="0" dur="' . $animateDuration . 's" fill="freeze"/>' . "\n";
          $svg .= '</polyline>' . "\n";
        } else {
          $svg .= '<polyline points="' . implode(' ', $pts) . '" fill="none" stroke="' . $datasetStroke . '" stroke-width="' . $datasetStrokeWidth . '"/>' . "\n";
        }
      }
    }
    return $svg;
  }
}

/**
 * Renders a bar chart.
 */
class BarChart extends ChartType
{
  /**
   * Render the bar chart as SVG.
   *
   * For a basic bar chart, we group bars by unique x values.
   *
   * @return string
   */
  public function render(): string
  {
    $svg = '';

    // Get plot area dimensions.
    $plotWidth  = $this->dimensions['width'] - $this->dimensions['paddingLeft'] - $this->dimensions['paddingRight'];
    $plotHeight = $this->dimensions['height'] - $this->dimensions['paddingTop'] - $this->dimensions['paddingBottom'];

    // Get unique x values (categories) from all datasets.
    $categories = [];
    foreach ($this->config['datasets'] as $dataset) {
      foreach ($dataset['values'] as $point) {
        $categories[] = $point[0];
      }
    }
    $categories = array_values(array_unique($categories));
    sort($categories);
    $numCategories = count($categories);
    if ($numCategories === 0) {
      return '';
    }

    // Calculate the width allocated for each category.
    $categoryWidth = $plotWidth / $numCategories;
    // Allow some spacing between bars in the same category.
    $barSpacing = 4;

    // New: Convert animate value (ms) to seconds.
    $animateDuration = isset($this->config['animate']) ? floatval($this->config['animate']) / 1000 : 0;

    // Process each dataset. For grouped bar charts, we show bars side-by-side per category.
    $numDatasets = count($this->config['datasets']);
    foreach ($this->config['datasets'] as $dIndex => $dataset) {
      // Default bar color for sparklines.
      $barColor = 'black';
      if (isset($dataset['color'])) {
        $barColor = $dataset['color'];
      }
      if (isset($dataset['style']) && is_array($dataset['style']) && isset($dataset['style']['stroke'])) {
        $barColor = $dataset['style']['stroke'];
      }
      // Default bar stroke width (for bars we use fill color).
      $barStrokeWidth = 0;
      if (isset($dataset['style']) && is_array($dataset['style']) && isset($dataset['style']['stroke-width'])) {
        $barStrokeWidth = $dataset['style']['stroke-width'];
      }

      // For each category, check if the dataset has a value.
      foreach ($categories as $cIndex => $cat) {
        // Search for a matching point in the dataset.
        $value = 0;
        foreach ($dataset['values'] as $point) {
          if ($point[0] == $cat) {
            $value = $point[1];
            break;
          }
        }
        // Calculate the bar height (assuming bars grow upward from the x-axis).
        $barHeight = (($value - $this->minY) / ($this->maxY - $this->minY)) * $plotHeight;
        // X position: start of category plus an offset for grouping.
        $groupOffset = ($dIndex * ($categoryWidth / $numDatasets));
        $barWidth = ($categoryWidth / $numDatasets) - $barSpacing;
        $x = $this->dimensions['paddingLeft'] + ($cIndex * $categoryWidth) + $groupOffset;
        // Y position: start at bottom of plot area minus bar height.
        $y = $this->dimensions['height'] - $this->dimensions['paddingBottom'] - $barHeight;
        if ($animateDuration > 0) {
          $svg .= '<rect x="' . $x . '" y="' . ($this->dimensions['height'] - $this->dimensions['paddingBottom']) . '" width="' . $barWidth . '" height="0" fill="' . $barColor . '" stroke="none">' . "\n";
          $svg .= '<animate attributeName="height" from="0" to="' . $barHeight . '" dur="' . $animateDuration . 's" fill="freeze"/>' . "\n";
          $svg .= '<animate attributeName="y" from="' . ($this->dimensions['height'] - $this->dimensions['paddingBottom']) . '" to="' . $y . '" dur="' . $animateDuration . 's" fill="freeze"/>' . "\n";
          $svg .= '</rect>' . "\n";
        } else {
          $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $barWidth . '" height="' . $barHeight . '" fill="' . $barColor . '" stroke="none" />' . "\n";
        }
      }
    }
    return $svg;
  }
}

/**
 * Factory to create a chart type instance based on the configuration.
 */
class ChartTypeFactory
{
  /**
   * Create an instance of a ChartType subclass based on the type.
   *
   * @param string $type
   * @param array  $config
   * @param array  $dimensions
   * @param float  $minX
   * @param float  $maxX
   * @param float  $minY
   * @param float  $maxY
   * @return ChartType
   */
  public static function create(string $type, array $config, array $dimensions, float $minX, float $maxX, float $minY, float $maxY): ChartType
  {
    switch (strtolower($type)) {
      case 'bar':
        return new BarChart($config, $dimensions, $minX, $maxX, $minY, $maxY);
      case 'line':
      default:
        return new LineChart($config, $dimensions, $minX, $maxX, $minY, $maxY);
    }
  }
}

/**
 * Main GR class.
 *
 * This class handles the overall SVG document (title, grid, axes) and delegates
 * chart-specific rendering to a ChartType instance.
 */
class GR
{
  /** @var string */
  private $svg = '';
  /** @var array */
  private $config = [];

  public function __construct()
  {
    // Any initialization if needed.
  }

  /**
   * Create a plot based on the given configuration.
   *
   * @param array $config
   */
  public function plot(array $config)
  {
    $this->config = $config;
    $this->buildSVG();
  }

  /**
   * Return the generated SVG.
   *
   * @return string
   */
  public function output()
  {
    return $this->svg;
  }

  /**
   * Save the SVG to a file.
   *
   * @param string $filePath
   */
  public function save($filePath)
  {
    file_put_contents($filePath, $this->svg);
  }

  /**
   * Build the complete SVG output.
   */
  private function buildSVG()
  {
    // Set simple sparkline defaults.
    $defaultContainer = [
      'width'         => 100,
      'height'        => 30,
      'padding-top'   => 0,
      'padding-right' => 0,
      'padding-bottom' => 0,
      'padding-left'  => 0,
    ];
    $containerConfig = $this->config['style']['container'] ?? [];
    $container = array_merge($defaultContainer, $containerConfig);

    $width  = $container['width'];
    $height = $container['height'];
    $paddingTop    = $container['padding-top'];
    $paddingRight  = $container['padding-right'];
    $paddingBottom = $container['padding-bottom'];
    $paddingLeft   = $container['padding-left'];
    $dimensions = [
      'width'         => $width,
      'height'        => $height,
      'paddingTop'    => $paddingTop,
      'paddingRight'  => $paddingRight,
      'paddingBottom' => $paddingBottom,
      'paddingLeft'   => $paddingLeft,
    ];

    // Start SVG.
    $svg  = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . "\n";
    $svg .= '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">' . "\n";

    // Draw title only if provided.
    if (!empty($this->config['title'])) {
      $titleX = $width / 2;
      $titleY = $paddingTop / 2;
      $svg .= '<text x="' . $titleX . '" y="' . $titleY . '" text-anchor="middle" font-size="20">'
        . htmlspecialchars($this->config['title']) . '</text>' . "\n";
    }

    // Determine plot area.
    $plotWidth  = $width - $paddingLeft - $paddingRight;
    $plotHeight = $height - $paddingTop - $paddingBottom;

    // Collect datasets to determine scaling.
    $datasets = $this->config['datasets'] ?? [];
    $allX = [];
    $allY = [];
    foreach ($datasets as $dataset) {
      foreach ($dataset['values'] as $point) {
        $allX[] = $point[0];
        $allY[] = $point[1];
      }
    }
    $minX = !empty($allX) ? min($allX) : 0;
    $maxX = !empty($allX) ? max($allX) : 1;
    $minY = !empty($allY) ? min($allY) : 0;
    $maxY = !empty($allY) ? max($allY) : 1;
    if ($maxX == $minX) {
      $maxX = $minX + 1;
    }
    if ($maxY == $minY) {
      $maxY = $minY + 1;
    }

    /*
         * --- Draw Grid Lines (only if explicitly defined) ---
         */
    if (!empty($this->config['style']['x-grid'])) {
      $xGridStyle = $this->config['style']['x-grid'];
      $gridXStroke = $xGridStyle['stroke'] ?? '#f0f0f0';
      $gridXStrokeWidth = $xGridStyle['stroke-width'] ?? 1;
      $numXTicks = 6;
      for ($i = 0; $i < $numXTicks; $i++) {
        $tickValue = $minX + ($i / ($numXTicks - 1)) * ($maxX - $minX);
        $xPos = $paddingLeft + (($tickValue - $minX) / ($maxX - $minX)) * $plotWidth;
        $svg .= '<line x1="' . $xPos . '" y1="' . $paddingTop . '" x2="' . $xPos
          . '" y2="' . ($height - $paddingBottom) . '" stroke="' . $gridXStroke
          . '" stroke-width="' . $gridXStrokeWidth . '"/>' . "\n";
      }
    }

    if (!empty($this->config['style']['y-grid'])) {
      $yGridStyle = $this->config['style']['y-grid'];
      $gridYStroke = $yGridStyle['stroke'] ?? '#f0f0f0';
      $gridYStrokeWidth = $yGridStyle['stroke-width'] ?? 1;
      $numYTicks = 6;
      for ($i = 0; $i < $numYTicks; $i++) {
        $tickValue = $minY + ($i / ($numYTicks - 1)) * ($maxY - $minY);
        $yPos = $height - $paddingBottom - (($tickValue - $minY) / ($maxY - $minY)) * $plotHeight;
        $svg .= '<line x1="' . $paddingLeft . '" y1="' . $yPos . '" x2="' . ($width - $paddingRight)
          . '" y2="' . $yPos . '" stroke="' . $gridYStroke . '" stroke-width="' . $gridYStrokeWidth . '"/>' . "\n";
      }
    }

    /*
         * --- Draw Axes (only if explicitly defined) ---
         */
    if (!empty($this->config['style']['axis'])) {
      $axisStyle = $this->config['style']['axis'];
      $axisStroke = $axisStyle['stroke'] ?? 'black';
      $axisStrokeWidth = $axisStyle['stroke-width'] ?? 1;
      // X-axis.
      $svg .= '<line x1="' . $paddingLeft . '" y1="' . ($height - $paddingBottom) . '" x2="' . ($width - $paddingRight)
        . '" y2="' . ($height - $paddingBottom) . '" stroke="' . $axisStroke . '" stroke-width="' . $axisStrokeWidth . '"/>' . "\n";
      // Y-axis.
      $svg .= '<line x1="' . $paddingLeft . '" y1="' . $paddingTop . '" x2="' . $paddingLeft . '" y2="' . ($height - $paddingBottom)
        . '" stroke="' . $axisStroke . '" stroke-width="' . $axisStrokeWidth . '"/>' . "\n";
    }

    /*
         * --- Render Chart Data ---
         */
    $chartType = $this->config['type'] ?? 'line';
    $chart = ChartTypeFactory::create($chartType, $this->config, $dimensions, $minX, $maxX, $minY, $maxY);
    $svg .= $chart->render();

    $svg .= '</svg>';
    $this->svg = $svg;
  }
}
