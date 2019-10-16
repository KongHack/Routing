<?php
namespace GCWorld\Routing\Exceptions;

use Exception;
use GCWorld\Routing\Hook;
use GCWorld\Routing\Interfaces\RouterExceptionInterface;

/**
 * Class RouterException403
 * @package GCWorld\Routing\Exceptions
 */
class RouterException403 extends Exception implements RouterExceptionInterface
{
    /**
     * RouterException403 constructor.
     * @param string          $message
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return void
     */
    public function executeLogic(): void
    {
        Hook::fire('403');
    }
}
