<?php
namespace GCWorld\Routing\Interfaces;

/**
 * Interface HandlerInterface
 * @package GCWorld\Routing\Interfaces
 * @deprecated Please switch to AdvancedHandlerInterface
 */
interface HandlerInterface
{
    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return void
     */
    public function setBreadcrumbs();
}
