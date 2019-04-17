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
     * @param string|array    $node
     * @param string          $message
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct($node, $message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->node = $node;
    }

    /**
     * @return void
     */
    public function executeLogic(): void
    {
        if(is_array($this->node)) {
            Hook::fire('403_pex', ['nodes'=>$this->node]);
            return;
        }
        Hook::fire('403_pex', ['node'=>$this->node]);
    }
}