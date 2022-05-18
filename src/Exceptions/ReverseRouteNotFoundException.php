<?php
namespace GCWorld\Routing\Exceptions;

use Exception;

/**
 * Class ReverseRouteNotFoundException
 * @package GCWorld\Routing\Exceptions
 */
class ReverseRouteNotFoundException extends Exception
{
    protected string $route;
    protected ?array $params;

    /**
     * ReverseRouteNotFoundException constructor.
     * @param string         $route
     * @param array|null     $params
     * @param string         $message
     * @param int            $code
     * @param Exception|null $previous
     */
    public function __construct(string $route, ?array $params = null, string $message = "", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->route  = $route;
        $this->params = $params;
    }
}
