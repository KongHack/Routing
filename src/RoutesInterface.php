<?php
namespace GCWorld\Routing;

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
