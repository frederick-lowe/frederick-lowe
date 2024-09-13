<?php

namespace Ido\Traits;

trait Storable {

	public function setDocument(?\Ido\Classes\Document $document) : void 
	{
		$this->document = $document ?? null;
	}

	public function getDocument() : ?\Ido\Clases\Document  
	{
		return $this->document ?? null;
	}

}