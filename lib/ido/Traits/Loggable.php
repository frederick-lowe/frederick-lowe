<?php
namespace Ido\Traits;

use Ido\Classes\Log;

/**
 * Trait Loggable
 * 
 * Provides logging functionality to classes that use this trait.
 */
trait Loggable
{
    /**
     * @var Log|null The logging object
     */
    protected ?Log $log = null;

    /**
     * Sets the logging object.
     *
     * @param Log|null $log The logging object to set
     * @return void
     */
    public function setLog(?Log $log): void
    {
        $this->log = $log;
    }

    /**
     * Retrieves the current logging object.
     *
     * @return Log|null The current logging object or null if not set
     */
    public function getLog(): ?Log
    {
        return $this->log;
    }
}
?>