<?php
namespace GCWorld\Routing\Interfaces;

/**
 * Interface RawRoutesInterface
 * @package GCWorld\Routing
 */
interface RawRoutesInterface
{
    /**
     * @return int
     */
    public function getFileTime();

    /**
     * @return array
     */
    public function getRoutes();
}
