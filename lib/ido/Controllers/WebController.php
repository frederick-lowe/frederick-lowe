<?php

namespace Ido\Controllers;

use Ido\Base\Controller;
use Ido\Classes\Config;
use Ido\Classes\Log;
use Ido\Traits\Configurable;
use Ido\Traits\Loggable;
use Ido\Traits\Storable;
use InvalidArgumentException;
use RuntimeException;

class WebController extends Controller 
{
    use Configurable;
    use Loggable;
    use Storable;

    private static $counter;

    private string $docroot;
    private string $route;
    private string $content;
    private int $renderDepth;

    public function __construct(\Ido\Classes\Config $config, \Ido\Classes\Log $log, \Ido\Classes\Document $document) 
    {
        $this->setConfig($config);
        $this->setLog($log);
        $this->setDocument($document);
    }

    public function getDocroot() : string 
    {
        return $this->docroot ??= $_SERVER['DOCUMENT_ROOT'];
    }

    public function getRoute() : string 
    {
        return $this->route ??= $_SERVER['REQUEST_URI'];
    }

    public function run(): void 
    {    
        $this->render('elements/doctype.html');
    }

    public function milliTime(): int {
        self::$counter += 1;

        $milliTime = (int)(microtime(true) * 1000);

        return (int)($milliTime + self::$counter);
    }

    /**
     * Replace macros in content with data from an associative array.
     * Recursively traverses the array to handle nested macros.
     * Macros without corresponding data are removed from the content.
     *
     * @param string $content The content containing macros
     * @param array $data The data to replace macros with
     * @return string The content with macros replaced
     */
    private function replaceMacros(string $content, array $data): string 
    {
        if(str_contains($content, '{%blog-post%}')) {
            $this->renderArticle($content);
        }

        $callback = function ($matches) use ($data) 
        {
            $key = trim($matches[1], '%');
            $keys = explode('.', $key);
            $value = $data;

            foreach ($keys as $k) {
                if (!isset($value[$k])) 
                {
                    return '';
                }
                $value = $value[$k];
            }

            return (string) $value;
        };

        return preg_replace_callback('/\{%([^}]+)%\}/', $callback, $content);
    }

    public function render(string $file): void 
    {
        if ($this->renderDepth === 0) 
        {
            ob_start();
        }

        $resource = $this->getDocRoot() . DIRECTORY_SEPARATOR . $file;
        
        if (!file_exists($resource)) 
        {
            echo "<!-- Resource {$resource} not found -->" . PHP_EOL;
            return;
        }

        $data = $this->getPageConfig();

        try 
        {
            $this->renderDepth++;
            include $resource;
        } 
        finally 
        {
            $this->renderDepth--;
        }

        if ($this->renderDepth === 0) 
        {
            $buffer = ob_get_clean();
            $this->content = $this->replaceMacros($buffer, $data);
            $this->content = $this->minimize($this->content);
            echo $this->content;
        }
    }

    private function minimize(string $content): string {
        return implode('', array_filter(preg_split("/(\n|\t|\s{2,})/", $content)));
    }

}