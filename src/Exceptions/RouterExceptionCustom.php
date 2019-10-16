<?php
namespace GCWorld\Routing\Exceptions;

use Exception;
use GCWorld\Routing\Hook;
use GCWorld\Routing\Interfaces\RouterExceptionInterface;

/**
 * Class RouterExceptionCustom
 * @package GCWorld\Routing\Exceptions
 */
class RouterExceptionCustom extends Exception implements RouterExceptionInterface
{
    private $title = '';

    /**
     * RouterExceptionCustom constructor.
     * @param string          $title
     * @param string          $message
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct(string $title = "", string $message = "", int $code = 200, Exception $previous = null)
    {
        $this->title = $title;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return void
     */
    public function executeLogic(): void
    {
        http_response_code($this->code);
        Hook::fire('custom',['title'=>$this->getTitle(),'message'=>$this->getMessage()]);
    }
}
