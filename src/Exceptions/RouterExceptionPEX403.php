<?php
namespace GCWorld\Routing\Exceptions;

use Exception;
use GCWorld\Routing\Core\CoreRouter;
use GCWorld\Routing\Hook;
use GCWorld\Routing\Interfaces\RouterExceptionInterface;

/**
 * Class RouterExceptionPEX403
 * @package GCWorld\Routing\Exceptions
 */
class RouterExceptionPEX403 extends Exception implements RouterExceptionInterface
{
    protected string|array $node = [];

    /**
     * RouterExceptionPEX403 constructor.
     * @param string|array   $node
     * @param string         $message
     * @param int            $code
     * @param Exception|null $previous
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
            Hook::fire(CoreRouter::getInstance()->getName(), '403_pex', ['nodes'=>$this->node]);
            return;
        }
        Hook::fire(CoreRouter::getInstance()->getName(), '403_pex', ['nodes'=>[$this->node]]);
    }
}
