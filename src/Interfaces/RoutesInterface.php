<?php
namespace GCWorld\Routing\Interfaces;

/**
 * Interface RoutesInterface
 */
interface RoutesInterface
{
    /**
     * @return integer
     */
    public function getFileTime();

    /**
     * @return array
     */
    public function getForwardRoutes();

    /**
     * @return array
     */
    public function getReverseRoutes();
}
