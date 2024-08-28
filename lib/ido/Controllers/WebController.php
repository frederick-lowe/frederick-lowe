<?php

namespace Ido\Controllers;

class WebController extends \Ido\Base\Controller 
{ 
	use \Ido\Traits\Configurable;
	use \Ido\Traits\Loggable;

	public function __construct (
		\Ido\Classes\Config $config,
		\Ido\Classes\Log $log
	) 
	{		
		if($config) {
			$this->setConfig($config);
		}

		if($log) {
			$this->setLog($log);
		}
	}

	public function run() : void 
	{
		echo 'WebController::run' . PHP_EOL;
	}

}
?>