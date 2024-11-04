<?php
namespace Ido\Traits;

use Ido\Classes\Config;

/**
 * Trait Configurable
 * 
 * Provides configuration management functionality to classes that use this trait.
 */
trait Configurable
{
    /**
     * @var Config|null The configuration object
     */
    protected ?Config $config = null;

    /**
     * Sets the configuration object.
     *
     * @param Config $config The configuration object to set
     * @return void
     */
    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }

    /**
     * Retrieves the current configuration object.
     *
     * @return Config|null The current configuration object or null if not set
     */
    public function getConfig(): ?Config
    {
        return $this->config;
    }
}
?>