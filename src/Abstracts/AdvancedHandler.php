<?php
namespace GCWorld\Routing\Abstracts;

use GCWorld\Routing\Interfaces\AdvancedHandlerInterface;
use GCWorld\Routing\Exceptions\RouterException404;

/**
 * Class AdvancedHandler
 */
abstract class AdvancedHandler implements AdvancedHandlerInterface
{
    protected $args = [];

    /**
     * @return void
     */
    public function setBreadcrumbs(): void
    {
        // Placeholder
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return 'TITLE NOT SET';
    }

    /**
     * AdvancedHandler constructor.
     * @param array $args
     */
    public function __construct(array $args)
    {
        $this->args = $args;
    }

    /**
     * @return string
     * @throws RouterException404
     */
    public function get(): string
    {
        throw new RouterException404();
    }

    /**
     * @return string
     * @throws RouterException404
     */
    public function getXHR(): string
    {
        throw new RouterException404();
    }

    /**
     * @return string
     * @throws RouterException404
     */
    public function post(): string
    {
        throw new RouterException404();
    }

    /**
     * @return string
     * @throws RouterException404
     */
    public function postXHR(): string
    {
        throw new RouterException404();
    }

    /**
     * @return string
     * @throws RouterException404
     */
    public function head(): string
    {
        throw new RouterException404();
    }

    /**
     * @return string
     * @throws RouterException404
     */
    public function headXHR(): string
    {
        throw new RouterException404();
    }

    /**
     * @return string
     * @throws RouterException404
     */
    public function put(): string
    {
        throw new RouterException404();
    }

    /**
     * @return string
     * @throws RouterException404
     */
    public function putXHR(): string
    {
        throw new RouterException404();
    }

    /**
     * @return string
     * @throws RouterException404
     */
    public function delete(): string
    {
        throw new RouterException404();
    }

    /**
     * @return string
     * @throws RouterException404
     */
    public function deleteXHR(): string
    {
        throw new RouterException404();
    }

    /**
     * @return string
     * @throws RouterException404
     */
    public function connect(): string
    {
        throw new RouterException404();
    }
    /**
     * @return string
     * @throws RouterException404
     */
    public function connectXHR(): string
    {
        throw new RouterException404();
    }

    /**
     * @return string
     * @throws RouterException404
     */
    public function options(): string
    {
        throw new RouterException404();
    }

    /**
     * @return string
     * @throws RouterException404
     */
    public function optionsXHR(): string
    {
        throw new RouterException404();
    }

    /**
     * @return string
     * @throws RouterException404
     */
    public function trace(): string
    {
        throw new RouterException404();
    }

    /**
     * @return string
     * @throws RouterException404
     */
    public function traceXHR(): string
    {
        throw new RouterException404();
    }

    /**
     * @return string
     * @throws RouterException404
     */
    public function patch(): string
    {
        throw new RouterException404();
    }

    /**
     * @return string
     * @throws RouterException404
     */
    public function patchXHR(): string
    {
        throw new RouterException404();
    }

}
