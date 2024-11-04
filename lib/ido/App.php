<?php
/**
 * Core application class handling autoloading and routing.
 */
class App
{
    /** @var array Mapping of namespaces to directories */
    protected $localNamespaceMap = [];

    /**
     * @param array $localNamespaceMap Mapping of namespaces to directories
     */
    public function __construct(array $localNamespaceMap = [])
    {
        $this->localNamespaceMap = $localNamespaceMap;
    }

    /**
     * Registers the autoloader.
     */
    public function register()
    {
        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * Autoloads classes based on namespace mapping.
     * 
     * @param string $className Full class name to autoload
     * @return bool True if file was loaded, false otherwise
     */
    public function autoload($className)
    {
        foreach ($this->localNamespaceMap as $namespacePrefix => $baseDir) {
            if (strpos($className, $namespacePrefix) === 0) {
                $relativeClass = str_replace($namespacePrefix, '', $className);
                $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
                if ($this->requireFile($file)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Requires a file if it exists.
     * 
     * @param string $file File path to require
     * @return bool True if file was required, false if file doesn't exist
     */
    protected function requireFile($file)
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }
        return false;
    }

    /**
     * Determines the current route based on execution context.
     * 
     * @return string 'cli', 'api', or 'src'
     */
    public function getRoute() : string
    {
        if (PHP_SAPI === 'cli') {
            return 'cli';
        }
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
            return 'api';
        }
        return 'src';
    }

    /**
     * Runs the application.
     */
    public function run() : void {
        $route = $this->getRoute();
        $controller = $this->getController($route);
        $controller->run();
    }

    /**
     * Gets the appropriate controller for the given route.
     * 
     * @param string $route Route identifier
     * @return object The controller instance
     */
    protected function getController($route)
    {
        $config = new \Ido\Classes\Config();
        $log = new \Ido\Classes\Log();
        $document = new \Ido\Classes\Document();

        switch($route) {
            case 'api':
                return new \Ido\Controllers\APIController($config, $log, $document);
            case 'cli':
                return new \Ido\Controllers\CLIController($config, $log, $document);
            default:
            case 'src':
                return new \Ido\Controllers\WebController($config, $log, $document);
        }
    }
}

/**
 * Application entry point.
 */
try {
    $app = new App(['Ido' => 'lib/ido']);
    $app->register();
    $app->run();
}
catch(Exception $e) {
    // Log the error
    error_log($e->getMessage());
    // Display a user-friendly message
    echo "An error occurred. Please try again later.";
}
?>