<?php

namespace Ido;

class APIController extends \Ido\Base\Controller { 
	public function __construct (
		\Ido\Classes\Config $config,
		\Ido\Classes\Log $log
	) 
	{

	}

	public function run() : void 
	{
		echo 'APIController::run' . PHP_EOL;
	}
}

?>