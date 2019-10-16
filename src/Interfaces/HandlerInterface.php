<?php
namespace GCWorld\Routing\Interfaces;

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
