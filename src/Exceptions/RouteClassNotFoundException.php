<?php
namespace GCWorld\Routing\Exceptions;

use Exception;

/**
 * Class RouteClassNotFoundException
 * @package GCWorld\Routing\Exceptions
 */
class RouteClassNotFoundException extends Exception
{
    protected string $route;
    protected ?array $params;

    /**
     * RouteClassNotFoundException constructor.
     * @param string         $class
     * @param array|null     $params
     * @param string         $message
     * @param int            $code
     * @param Exception|null $previous
     */
    public function __construct(string $class, ?array $params = null, string $message = "", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->route  = $class;
        $this->params = $params;

        if(empty($message)) {
            $message = 'Routed Class Not Found: '.$class;
        }
    }
}
