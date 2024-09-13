<?php

namespace Ido\Controllers;

use Ido\Base\Controller;
use Ido\Classes\Config;
use Ido\Classes\Log;
use Ido\Traits\Configurable;
use Ido\Traits\Loggable;
use InvalidArgumentException;
use RuntimeException;

class CLIController extends \Ido\Base\Controller { 
    use Configurable;
    use Loggable;

    public function __construct(\Ido\Classes\Config $config, \Ido\Classes\Log $log) 
    {
        $this->setConfig($config);
        $this->setLog($log);
    }

	public function run() : void 
	{
		phpinfo();
		echo 'CLIController::run (' . __FILE__ . ')' . PHP_EOL;
	}
}

?>
