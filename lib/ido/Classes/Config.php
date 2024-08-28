<?php

namespace Ido\Classes;

class Config { 

	private $settings = [];

	public function __construct () 
	{

	}

	public function get(string $name) : mixed
	{
		return $this->settings[$name] ?? null;
	}

	public function set(string $name, mixed $value) : void 
	{
		$this->settings[$name] = $value;
	}

	public function run() : void 
	{

	}

}

?>
