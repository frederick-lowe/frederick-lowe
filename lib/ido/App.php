<?php
class App
{
    protected $localNamespaceMap = [];

    public function __construct(array $localNamespaceMap = [])
    {
        $this->localNamespaceMap = $localNamespaceMap;
    }

    public function register()
    {
        spl_autoload_register([$this, 'autoload']);
    }

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

    protected function requireFile($file)
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }

        return false;
    }

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

    public function run() : void {
        
        switch($this->getRoute()) {
            case 'api':
                $controller = new \Ido\Controllers\APIController(
                    new \Ido\Classes\Config(),
                    new \Ido\Classes\Log()
                );
            break;

            case 'cli':
                $controller = new \Ido\Controllers\CLIController(
                    new \Ido\Classes\Config(),
                    new \Ido\Classes\Log()
                );
            break;

            default:
            case 'src':
                $controller = new \Ido\Controllers\WebController(
                    new \Ido\Classes\Config(),
                    new \Ido\Classes\Log()
                );
            break;
        }

        $controller->run();
    }
}

try {
    $app = new App(['Ido' => 'lib/ido']);
    $app->register();
    $app->run();
}
catch(Exception $e) {
    print_r($e);
}
?>
