<?php
namespace GCWorld\Routing\Exceptions;

use Exception;
use GCWorld\Routing\Core\CoreRouter;
use GCWorld\Routing\Hook;
use GCWorld\Routing\Interfaces\RouterExceptionInterface;

/**
 * Class RouterException404
 * @package GCWorld\Routing\Exceptions
 */
class RouterException404 extends Exception implements RouterExceptionInterface
{
    /**
     * RouterException404 constructor.
     * @param string          $message
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return void
     */
    public function executeLogic(): void
    {
        Hook::fire(CoreRouter::getInstance()->getName(), '404');
    }
}
