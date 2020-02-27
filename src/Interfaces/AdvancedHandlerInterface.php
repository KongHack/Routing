<?php
namespace GCWorld\Routing\Interfaces;

/**
 * Interface AdvancedHandlerInterface
 */
interface AdvancedHandlerInterface
{
    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * @return void
     */
    public function setBreadcrumbs(): void;

    /**
     * @return string
     */
    public function get(): string;

    /**
     * @return string
     */
    public function getXHR(): string;

    /**
     * @return string
     */
    public function post(): string;

    /**
     * @return string
     */
    public function postXHR(): string;

    /**
     * @return string
     */
    public function head(): string;

    /**
     * @return string
     */
    public function headXHR(): string;

    /**
     * @return string
     */
    public function put(): string;

    /**
     * @return string
     */
    public function putXHR(): string;

    /**
     * @return string
     */
    public function delete(): string;

    /**
     * @return string
     */
    public function deleteXHR(): string;

    /**
     * @return string
     */
    public function connect(): string;

    /**
     * @return string
     */
    public function connectXHR(): string;

    /**
     * @return string
     */
    public function options(): string;

    /**
     * @return string
     */
    public function optionsXHR(): string;

    /**
     * @return string
     */
    public function trace(): string;

    /**
     * @return string
     */
    public function traceXHR(): string;

    /**
     * @return string
     */
    public function patch(): string;

    /**
     * @return string
     */
    public function patchXHR(): string;    
}
