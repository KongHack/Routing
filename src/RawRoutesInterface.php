<?php
namespace GCWorld\Routing;

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
