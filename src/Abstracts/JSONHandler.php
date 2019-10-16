<?php
namespace GCWorld\Routing\Abstracts;

use GCWorld\Routing\Exceptions\RouterException404;
use GCWorld\Routing\Interfaces\JSONHandlerInterface;

/**
 * Class JSONHandler
 */
abstract class JSONHandler implements JSONHandlerInterface
{
    protected $args = [];

    /**
     * JSONHandler constructor.
     * @param array $args
     */
    public function __construct(array $args)
    {
        $this->args = $args;
    }

    /**
     * @return array
     * @throws RouterException404
     */
    public function getXHR(): array
    {
        throw new RouterException404();
    }

    /**
     * @return array
     * @throws RouterException404
     */
    public function postXHR(): array
    {
        throw new RouterException404();
    }

    /**
     * @return array
     * @throws RouterException404
     */
    public function headXHR(): array
    {
        throw new RouterException404();
    }

    /**
     * @return array
     * @throws RouterException404
     */
    public function putXHR(): array
    {
        throw new RouterException404();
    }

    /**
     * @return array
     * @throws RouterException404
     */
    public function deleteXHR(): array
    {
        throw new RouterException404();
    }

    /**
     * @return array
     * @throws RouterException404
     */
    public function connectXHR(): array
    {
        throw new RouterException404();
    }

    /**
     * @return array
     * @throws RouterException404
     */
    public function optionsXHR(): array
    {
        throw new RouterException404();
    }

    /**
     * @return array
     * @throws RouterException404
     */
    public function traceXHR(): array
    {
        throw new RouterException404();
    }

    /**
     * @return array
     * @throws RouterException404
     */
    public function patchXHR(): array
    {
        throw new RouterException404();
    }

}
