<?php
namespace GCWorld\Routing\Exceptions;

use Exception;
use GCWorld\Routing\Hook;
use GCWorld\Routing\RouterExceptionInterface;

/**
 * Class RouterExceptionPEX403
 * @package GCWorld\Routing\Exceptions
 */
class RouterExceptionPEX403 extends Exception implements RouterExceptionInterface
{
    protected $node = null;

    /**
     * RouterException403 constructor.
     * @param string          $node
     * @param string          $message
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct(string $node, $message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->node = $node;
    }

    /**
     * @return void
     */
    public function executeLogic(): void
    {
        Hook::fire('403_pex', ['node'=>$this->node]);
    }
}
