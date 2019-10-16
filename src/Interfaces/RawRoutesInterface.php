<?php
namespace GCWorld\Routing\Interfaces;

/**
 * Interface RawRoutesInterface
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
