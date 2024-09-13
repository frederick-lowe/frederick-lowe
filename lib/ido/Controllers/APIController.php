<?php
namespace Ido\Controllers;

use Ido\Base\Controller;
use Ido\Classes\Config;
use Ido\Classes\Log;
use Ido\Traits\Configurable;
use Ido\Traits\Loggable;
use InvalidArgumentException;
use RuntimeException;

class APIController extends \Ido\Base\Controller { 
    use Configurable;
    use Loggable;

    private string $endpoint;

    public function __construct(\Ido\Classes\Config $config, \Ido\Classes\Log $log) 
    {
        $this->setConfig($config);
        $this->setLog($log);
    }

    private function setEndpoint(): void {
    	$request = $_SERVER['REQUEST_URI'];
    	$script = $_SERVER['SCRIPT_FILENAME'];
    	$this->endpoint = preg_replace("/\/app/", $request, $script);
    }

    private function getEndpoint(): string {
    	if (!isset($this->endpoint)) {
    	    $this->setEndpoint();
    	}
    	return $this->endpoint;
    }

    public function run(): void 
    {
        $endpoint = $this->getEndpoint();

        if (!file_exists($endpoint)) {
            http_response_code(404);
            echo "HTTP 404 / Not Found";
            return;
        }

        include_once($endpoint);
    }
}
?>