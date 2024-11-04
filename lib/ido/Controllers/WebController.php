<?php
namespace Ido\Controllers;

use Ido\Base\Controller;
use Ido\Classes\{Config, Log, Document};
use Ido\Traits\{Configurable, Loggable, Storable};

class WebController extends Controller
{
    use Configurable, Loggable, Storable;

    private ?string $docroot = null;
    private string $content;
    private array $attributes = [];
    private int $renderDepth = 0;

    /* Valid HTML attributes and tags for O(1) lookup */
    private const HTML_ATTRIBUTES = [
        'as' => true, 'accept' => true, 'accept-charset' => true, 'accesskey' => true, 'action' => true,
        'align' => true, 'alt' => true, 'async' => true, 'autocomplete' => true, 'autofocus' => true,
        'autoplay' => true, 'bgcolor' => true, 'border' => true, 'charset' => true, 'checked' => true,
        'cite' => true, 'class' => true, 'color' => true, 'cols' => true, 'colspan' => true,
        'content' => true, 'contenteditable' => true, 'controls' => true, 'coords' => true,
        'crossorigin' => true, 'data' => true, 'data-src' => true, 'datetime' => true, 'default' => true, 'defer' => true, 'dir' => true,
        'dirname' => true, 'disabled' => true, 'download' => true, 'draggable' => true,
        'enctype' => true, 'for' => true, 'form' => true, 'formaction' => true, 'headers' => true,
        'height' => true, 'hidden' => true, 'high' => true, 'href' => true, 'hreflang' => true,
        'id' => true, 'integrity' => true, 'ismap' => true, 'kind' => true, 'label' => true,
        'lang' => true, 'list' => true, 'loop' => true, 'low' => true, 'max' => true,
        'maxlength' => true, 'media' => true, 'method' => true, 'min' => true, 'multiple' => true,
        'muted' => true, 'name' => true, 'novalidate' => true, 'open' => true, 'optimum' => true,
        'pattern' => true, 'placeholder' => true, 'poster' => true, 'preload' => true,
        'readonly' => true, 'rel' => true, 'required' => true, 'reversed' => true, 'rows' => true,
        'rowspan' => true, 'sandbox' => true, 'scope' => true, 'selected' => true, 'shape' => true,
        'size' => true, 'sizes' => true, 'span' => true, 'spellcheck' => true, 'src' => true,
        'srcdoc' => true, 'srclang' => true, 'srcset' => true, 'start' => true, 'step' => true,
        'style' => true, 'tabindex' => true, 'target' => true, 'title' => true, 'type' => true,
        'usemap' => true, 'value' => true, 'width' => true, 'wrap' => true
    ];

    private const HTML_TAGS = [
        'a' => true, 'abbr' => true, 'address' => true, 'area' => true, 'article' => true,
        'aside' => true, 'audio' => true, 'b' => true, 'base' => true, 'bdi' => true, 'bdo' => true,
        'blockquote' => true, 'body' => true, 'br' => true, 'button' => true, 'canvas' => true,
        'caption' => true, 'cite' => true, 'code' => true, 'col' => true, 'colgroup' => true,
        'data' => true, 'datalist' => true, 'dd' => true, 'del' => true, 'details' => true,
        'dfn' => true, 'dialog' => true, 'div' => true, 'dl' => true, 'dt' => true, 'em' => true,
        'embed' => true, 'fieldset' => true, 'figcaption' => true, 'figure' => true,
        'footer' => true, 'form' => true, 'h1' => true, 'h2' => true, 'h3' => true, 'h4' => true,
        'h5' => true, 'h6' => true, 'head' => true, 'header' => true, 'hr' => true, 'html' => true,
        'i' => true, 'iframe' => true, 'img' => true, 'input' => true, 'ins' => true, 'kbd' => true,
        'label' => true, 'legend' => true, 'li' => true, 'link' => true, 'main' => true,
        'map' => true, 'mark' => true, 'meta' => true, 'meter' => true, 'nav' => true,
        'noscript' => true, 'object' => true, 'ol' => true, 'optgroup' => true, 'option' => true,
        'output' => true, 'p' => true, 'param' => true, 'picture' => true, 'pre' => true,
        'progress' => true, 'q' => true, 'rp' => true, 'rt' => true, 'ruby' => true, 's' => true,
        'samp' => true, 'script' => true, 'section' => true, 'select' => true, 'small' => true,
        'source' => true, 'span' => true, 'strong' => true, 'style' => true, 'sub' => true,
        'summary' => true, 'sup' => true, 'table' => true, 'tbody' => true, 'td' => true,
        'template' => true, 'textarea' => true, 'tfoot' => true, 'th' => true, 'thead' => true,
        'time' => true, 'title' => true, 'tr' => true, 'track' => true, 'u' => true, 'ul' => true,
        'var' => true, 'video' => true, 'wbr' => true
    ];

    private const SELF_CLOSING_TAGS = [
        'area' => true, 'base' => true, 'br' => true, 'col' => true, 'embed' => true, 'hr' => true,
        'img' => true, 'input' => true, 'link' => true, 'meta' => true, 'param' => true,
        'source' => true, 'track' => true, 'wbr' => true
    ];

    public function __construct(Config $config, Log $log, Document $document) 
    {
        $this->setConfig($config);
        $this->setLog($log);
        $this->setDocument($document);
    }

    public function getDocroot(): string 
    {
        return $this->docroot ??= $_SERVER['DOCUMENT_ROOT'] ?? '';
    }

    public function getRequestRoute(): string 
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        return empty($uri) ? '/' : rtrim($uri, '/');
    }

    public function resolveRoute(): array 
    {
        return array_unique(array_merge(['/global.css', '/style.css', '/fonts.css', '/script.js'], [$this->getRequestRoute()]));
    }

    public function configure(): void 
    {
        foreach ($this->resolveRoute() as $route) {
            $data = $this->document->select('route', ['route' => $route], [], true);
            if (!empty($data)) {
                $this->config->loadFromArray($data);
            }
        }
    }

    public function unixToISO8601(int|string $time): string 
    {
        return is_numeric($time)
            ? (new \DateTime())->setTimestamp($time)->format(\DateTime::ATOM)
            : $time;
    }

    public function run(): void 
    {
        $this->configure();
        $this->config->set('minimize', true);
        $this->config->set('env.src', '/src');       

        ob_start();
        $this->render('doctype', $this->config->getSettings()['content']);
        $page = $this->removeUnusedMacros(ob_get_clean());

        echo $this->config->get('minimize') ? $this->minimize($page) : $page;
    }

    private function renderLDJson(): void 
    {
        $ld = $this->config->get('ld');
        if (empty($ld)) return;

        $ld = array_map(function($key, $value) {
            if (stripos($key, 'date') !== false) {
                return $this->unixToISO8601($value);
            }
            return is_array($value) ? array_diff_key($value, ['_id' => '']) : $value;
        }, array_keys($ld), $ld);

        echo '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES) . '</script>';
    }

    private function removeUnusedMacros(string $page): string 
    {
        $pattern = '/\s+[^\s=]+=(["\'])\{%.*?%\}\1/';
        $page = preg_replace($pattern, '', $page);
        $page = preg_replace_callback(
            '/([^\s=]+)=([\'"])(.*?)\2/',
            fn($matches) => $matches[1] . '=' . $matches[2] . preg_replace('/\s?\{%.*?%\}/', '', $matches[3]) . $matches[2],
            $page
        );
        return preg_replace('/\{%.*?%\}/', '', $page);
    }

    public function render(string $tag = '', array $data = []): void 
    {
        if (empty($tag)) return;

        if ($tag === 'textNode') {
            echo $this->replaceMacros($data[$tag], $data);
            return;
        }

        $element = $this->renderElement($tag, $data);
        echo $this->replaceMacros($element, $data);
    }

    private function renderElement(string $tag, array $data): string 
    {
        ob_start();
        $this->renderOpenTag($tag, $data);
        $this->renderChildren($tag, $data);
        $this->renderCloseTag($tag, $data);
        return ob_get_clean();
    }

    private function isAttribute(string $token): bool 
    {
        return isset(self::HTML_ATTRIBUTES[$token]) || strpos($token, 'data-') === 0 || strpos($token, 'aria-') === 0;
    }

    private function isHTMLTag(mixed $token): bool 
    {
        return is_string($token) && isset(self::HTML_TAGS[$token]);
    }

    private function isSelfClosingTag(mixed $token): bool 
    {
        return is_string($token) && isset(self::SELF_CLOSING_TAGS[$token]);
    }

    private function renderStyleSheet(array $styles, bool $isNested = false): string 
    {
        $css = '';
        if (!$isNested) $css .= '<style>';

        foreach ($styles as $selector => $properties) {
            if ($selector === 'children' && is_array($properties)) {
                foreach ($properties as $child) {
                    $css .= $this->renderStyleSheet($child, true);
                }
            } else {
                $css .= $selector . ' { ';
                foreach ($properties as $property => $value) {
                    if (!is_array($value)) {
                        $css .= $property . ': ' . $value . '; ';
                    }
                }
                $css .= '} ';
            }
        }

        if (!$isNested) $css .= '</style>';
        return $css;
    }

    private function renderOpenTag(string $tag, array $data): void 
    {
        if ($tag === 'doctype') {
            echo '<!DOCTYPE html>';
            return;
        }

        if ($this->isHTMLTag($tag)) {
            $tagData = $data[$tag] ?? '';
            echo '<' . $tag . ' ' . $this->getAttributes($tagData);
            echo $this->isSelfClosingTag($tag) ? '/>' : '>';
        } else {
            echo "<!-- nabro $tag -->";
        }
    }

    private function getAttributes(array $data): string 
    {
        return implode(' ', array_filter(array_map(
            fn($attr, $value) => $this->isAttribute($attr) ? "$attr=\"$value\"" : '',
            array_keys($data), $data
        )));
    }

    private function renderCloseTag(string $tag, array $data): void 
    {
        if (!$this->isSelfClosingTag($tag)) {
            echo '</' . $tag . '>';
        }
    }

    private function renderChildren(string $tag, array $data): void 
    {
        $children = $data[$tag]['children'] ?? [];
        foreach ($children as $node) {
            foreach (array_keys($node) as $childTag) {
                $this->render($childTag, $node);
            }
        }
    }

    private function minimize(string $content): string 
    {
        return str_replace(
            ' >', '>', preg_replace(
                '/>\s+</', '><', preg_replace(
                    '/\s+/', ' ', $content
                )
            )
        );
    }

    private function replaceMacros(string $content, array $data): string 
    {
        return preg_replace_callback('/\{%(.+?)%\}/', function ($matches) use ($data) {
            $key = trim($matches[1], '%');
            $keys = explode('.', $key);
            
            if (strpos($key, 'env.') === 0) {
                return trim($this->config->get($key));
            }

            $value = $data;
            foreach ($keys as $k) {
                if (!isset($value[$k])) return '';
                $value = $value[$k];
            }

            return trim((string) $value);
        }, $content);
    }
}
