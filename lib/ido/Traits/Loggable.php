<?php

namespace Ido\Traits;

trait Loggable {

	public function setLog(?\Ido\Classes\Log $log) : void 
	{
		$this->log = $log ?? null;
	}

	public function getLog() : ?\Ido\Clases\Log  
	{
		return $this->log ?? null;
	}

}