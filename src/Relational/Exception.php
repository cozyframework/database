<?php

namespace Cozy\Database\Relational;

class Exception extends \RuntimeException
{
    /** @var string */
    protected $sentence;
    /** @var string */
    protected $code;
    /** @var array */
    protected $errorInfo = [];

    /**
     * Exception constructor.
     *
     * @param string $message
     * @param mixed $code
     * @param array $error_info
     * @param string|null $sentence
     */
    public function __construct(string $message = '', $code = null, array $error_info = [], string $sentence = '')
    {
        parent::__construct($message, 0);

        $this->code = $code;
        $this->errorInfo = $error_info;
        $this->sentence = $sentence;
    }

    /**
     * Gets the SQL sentence
     * @return string the SQL sentence as a string.
     */
    public function getSentence(): string
    {
        return $this->sentence;
    }

    /**
     * Gets detailed information of error
     * @return array the PDO error information as an array.
     */
    public function getErrorInfo(): array
    {
        return $this->errorInfo;
    }
}
