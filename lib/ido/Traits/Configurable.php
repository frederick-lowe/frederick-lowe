<?php

namespace Ido\Traits;

trait Configurable
{
    protected ?\Ido\Classes\Config $config = null;

    public function setConfig(\Ido\Classes\Config $config): void
    {
        $this->config = $config;
    }

    public function getConfig(): ?\Ido\Classes\Config
    {
        return $this->config;
    }
}

?>
