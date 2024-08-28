<?php

namespace Ido\Traits;

trait Configurable {

	public function setConfig(?\Ido\Classes\Config $config) : void 
	{
		$this->config = $config ?? null;
	}

	public function getConfig() : ?\Ido\Clases\Config  
	{
		return $this->config ?? null;
	}

}