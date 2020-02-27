<?php
namespace GCWorld\Routing\Interfaces;

/**
 * Interface JSONHandlerInterface
 */
interface JSONHandlerInterface
{
    /**
     * @return array
     */
    public function getXHR(): array;

    /**
     * @return array
     */
    public function postXHR(): array;

    /**
     * @return array
     */
    public function headXHR(): array;

    /**
     * @return array
     */
    public function putXHR(): array;

    /**
     * @return array
     */
    public function deleteXHR(): array;

    /**
     * @return array
     */
    public function connectXHR(): array;

    /**
     * @return array
     */
    public function optionsXHR(): array;

    /**
     * @return array
     */
    public function traceXHR(): array;

    /**
     * @return array
     */
    public function patchXHR(): array;
}