<?php
namespace Ido\Traits;

use Ido\Classes\Document;

/**
 * Trait Storable
 * 
 * Provides document storage functionality to classes that use this trait.
 */
trait Storable
{
    /**
     * @var Document|null The document object
     */
    protected ?Document $document = null;

    /**
     * Sets the document object.
     *
     * @param Document|null $document The document object to set
     * @return void
     */
    public function setDocument(?Document $document): void
    {
        $this->document = $document;
    }

    /**
     * Retrieves the current document object.
     *
     * @return Document|null The current document object or null if not set
     */
    public function getDocument(): ?Document
    {
        return $this->document;
    }
}
?>