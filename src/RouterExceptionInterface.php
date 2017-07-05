<?php
namespace GCWorld\Routing;

interface RouterExceptionInterface
{
    public function executeLogic(): void;
}