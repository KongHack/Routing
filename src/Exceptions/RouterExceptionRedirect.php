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
    private $redirectUrl = '';

    /**
     * RouterExceptionRedirect constructor.
     * @param string          $redirectUrl
     * @param string          $message
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct(string $redirectUrl = "", string $message = "", int $code = 0, Exception $previous = null)
    {
        $this->redirectUrl = $redirectUrl;
        parent::__construct($message, $code, $previous);
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
        header('Location: '.$this->getRedirectUrl());
    }
}
