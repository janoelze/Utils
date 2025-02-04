<?php

namespace JanOelze\Utils;

class UIElement {
    protected $type;
    protected $attributes = [];
    protected $children = [];
    protected $content = '';

    public function __construct(string $type, array $attributes = [], string $content = '') {
        $this->type = $type;
        $this->attributes = $attributes;
        $this->content = $content;
    }

    public function add(...$elements): self {
        foreach ($elements as $element) {
            $this->children[] = $element;
        }
        return $this;
    }

    protected function renderAttributes(array $skip = []): string {
        $str = '';
        foreach ($this->attributes as $key => $value) {
            if (in_array($key, $skip)) {
                continue;
            }
            $str .= htmlspecialchars($key) . '="' . htmlspecialchars($value) . '" ';
        }
        return trim($str);
    }

    public function render(): string {
        switch ($this->type) {
            case 'box':
                $direction = $this->attributes['direction'] ?? 'row';
                $style = "display:flex;flex-direction:$direction;";
                $attrs = $this->renderAttributes(['direction']);
                $html = "<div style=\"$style\" $attrs>";
                foreach ($this->children as $child) {
                    $html .= $child->render();
                }
                $html .= "</div>";
                return $html;

            case 'link':
                $attrs = $this->renderAttributes();
                return "<a $attrs>" . htmlspecialchars($this->content) . "</a>";

            case 'table':
                $attrs = $this->renderAttributes();
                $html = "<table $attrs>";
                foreach ($this->children as $child) {
                    $html .= $child->render();
                }
                $html .= "</table>";
                return $html;

            case 'tr':
                $attrs = $this->renderAttributes();
                $html = "<tr $attrs>";
                foreach ($this->children as $child) {
                    $html .= $child->render();
                }
                $html .= "</tr>";
                return $html;

            case 'td':
                $attrs = $this->renderAttributes();
                return "<td $attrs>" . htmlspecialchars($this->content) . "</td>";

            case 'grid':
                $columns = $this->attributes['columns'] ?? '1fr';
                $rows = $this->attributes['rows'] ?? 'auto';
                $gap = $this->attributes['gap'] ?? '0';
                $style = "display:grid; grid-template-columns: $columns; grid-template-rows: $rows; gap: $gap;";
                if (isset($this->attributes['style'])) {
                    $style .= $this->attributes['style'];
                }
                $attrs = $this->renderAttributes(['columns', 'rows', 'gap', 'style']);
                $html = "<div style=\"$style\" $attrs>";
                foreach ($this->children as $child) {
                    $html .= $child->render();
                }
                $html .= "</div>";
                return $html;

            // New element types for the HTML document structure:
            case 'html':
                $attrs = $this->renderAttributes();
                $html = "<html $attrs>";
                foreach ($this->children as $child) {
                    $html .= $child->render();
                }
                $html .= "</html>";
                return $html;

            case 'head':
                $attrs = $this->renderAttributes();
                $html = "<head $attrs>";
                foreach ($this->children as $child) {
                    $html .= $child->render();
                }
                $html .= "</head>";
                return $html;

            case 'body':
                $attrs = $this->renderAttributes();
                $html = "<body $attrs>";
                foreach ($this->children as $child) {
                    $html .= $child->render();
                }
                $html .= "</body>";
                return $html;

            case 'title':
                $attrs = $this->renderAttributes();
                return "<title $attrs>" . htmlspecialchars($this->content) . "</title>";

            case 'meta':
                $attrs = $this->renderAttributes();
                // Meta tags are self-closing.
                return "<meta $attrs>";

            case 'script':
                $attrs = $this->renderAttributes();
                return "<script $attrs>" . $this->content . "</script>";

            case 'stylesheet':
                // A stylesheet is just a link tag with rel="stylesheet"
                $this->attributes['rel'] = 'stylesheet';
                $attrs = $this->renderAttributes();
                return "<link $attrs>";

            default:
                $attrs = $this->renderAttributes();
                $html = "<div $attrs>" . $this->content;
                foreach ($this->children as $child) {
                    $html .= $child->render();
                }
                $html .= "</div>";
                return $html;
        }
    }
}

class UI {
    public function __call($name, $arguments) {
        switch ($name) {
            case 'box':
                $attributes = $arguments[0] ?? [];
                return new UIElement('box', $attributes);
            case 'link':
                $href = $arguments[0] ?? '#';
                $text = $arguments[1] ?? '';
                $attributes = $arguments[2] ?? [];
                $attributes['href'] = $href;
                return new UIElement('link', $attributes, $text);
            case 'table':
                $attributes = $arguments[0] ?? [];
                return new UIElement('table', $attributes);
            case 'tr':
                $attributes = $arguments[0] ?? [];
                return new UIElement('tr', $attributes);
            case 'td':
                $content = $arguments[0] ?? '';
                $attributes = $arguments[1] ?? [];
                return new UIElement('td', $attributes, $content);
            case 'grid':
                $attributes = $arguments[0] ?? [];
                return new UIElement('grid', $attributes);
            case 'html':
                $attributes = $arguments[0] ?? [];
                return new UIElement('html', $attributes);
            case 'head':
                $attributes = $arguments[0] ?? [];
                return new UIElement('head', $attributes);
            case 'body':
                $attributes = $arguments[0] ?? [];
                return new UIElement('body', $attributes);
            case 'title':
                $text = $arguments[0] ?? '';
                $attributes = $arguments[1] ?? [];
                return new UIElement('title', $attributes, $text);
            case 'meta':
                $attributes = $arguments[0] ?? [];
                return new UIElement('meta', $attributes);
            case 'script':
                $content = $arguments[0] ?? '';
                $attributes = $arguments[1] ?? [];
                return new UIElement('script', $attributes, $content);
            case 'stylesheet':
                $attributes = $arguments[0] ?? [];
                return new UIElement('stylesheet', $attributes);
            default:
                throw new \Exception("Unknown UI element type: $name");
        }
    }

    /**
     * Helper method to wrap the document in a doctype and the <html> element.
     */
    public function document(UIElement $head, UIElement $body): string {
        $html = "<!DOCTYPE html>\n";
        $doc = $this->html();
        $doc->add($head, $body);
        $html .= $doc->render();
        return $html;
    }
}
