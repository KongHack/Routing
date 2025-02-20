<?php
namespace GCWorld\Routing\Exceptions;

use Exception;
use GCWorld\Routing\Interfaces\RouterExceptionInterface;

/**
 * Class RouterExceptionRedirect
 * @package GCWorld\Routing\Exceptions
 */
class RouterExceptionRedirect extends Exception implements RouterExceptionInterface
{
    protected string $redirectUrl = '';
    protected int    $statusCode  = 0;

    /**
     * RouterExceptionRedirect constructor.
     * @param string          $redirectUrl
     * @param string          $message
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct(string $redirectUrl = "", string $message = "", int $code = 0, ?Exception $previous = null)
    {
        $this->redirectUrl = $redirectUrl;
        parent::__construct($message, $code, $previous);

        if($code > 0) {
            $this->statusCode = $code;
        }
        if($this->statusCode === 0) {
            if($_SERVER['REQUEST_METHOD']??'' === 'GET') {
                $this->statusCode = 301;
            } else {
                $this->statusCode = 302;
            }
        }
    }

    /**
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    /**
     * @return void
     */
    public function executeLogic(): void
    {
        header('Location: '.$this->getRedirectUrl(), true, $this->statusCode);
    }
}
