<?php

namespace Ido\Controllers;

use Ido\Base\Controller;
use Ido\Traits\Configurable;
use Ido\Traits\Loggable;
use Ido\Classes\Config;
use Ido\Classes\Log;
use InvalidArgumentException;
use RuntimeException;

class WebController extends Controller 
{
    use Configurable;
    use Loggable;

    private string $docRoot;
    private string $content;
    private string $page;
    private array $pageConfig = [];
    private string $pageConfigFile;
    private int $renderDepth = 0;

    public function __construct(Config $config, Log $log) 
    {
        $this->setConfig($config);
        $this->setLog($log);
        $this->docRoot = $this->setDocRoot();
        $this->page = $this->setPage();
    }

    public function run(): void 
    {
        $this->render('elements/doctype.html');
    }

    public function setDocRoot(?string $docRoot = null): string 
    {
        return $this->docRoot = $docRoot ?? $_SERVER['DOCUMENT_ROOT'];
    }

    public function getDocRoot(): string 
    {
        return $this->docRoot;
    }

    public function getPageConfig(): array 
    {
        if (empty($this->pageConfig)) 
        {
            $this->setPageConfig();
        }
        return $this->pageConfig;
    }

    private function setPageConfig(): void 
    {
        $this->pageConfigFile = 
        	$this->getDocRoot() . str_replace('html', 'json', $this->getPage());
        
        if (!file_exists($this->pageConfigFile)) 
        {
            $this->pageConfig = [];
            return;
        }

        try 
        {
            $configContent = file_get_contents($this->pageConfigFile);
            if ($configContent === false) 
            {
                throw new RuntimeException("Failed to read page config file: {$this->pageConfigFile}");
            }
            $decodedConfig = json_decode($configContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) 
            {
                throw new RuntimeException("Failed to parse JSON in page config file: " . json_last_error_msg());
            }
            $this->pageConfig = $decodedConfig;
        } 
        catch (\Exception $e) 
        {
            $this->log->error("Error setting page config: " . $e->getMessage());
            $this->pageConfig = [];
        }
    }

    public function getPage(): string 
    {
        return $this->page;
    }

    private function setPage(): string 
    {
        $page = str_replace('/src/', '/pages/', $_SERVER['REQUEST_URI']);
        if (!str_contains($page, 'index.html')) 
        {
            $page .= 'index.html';
        }
        return $page;
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
            $this->log->warning("Resource not found: {$resource}");
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
            echo $this->content;
        }
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
}