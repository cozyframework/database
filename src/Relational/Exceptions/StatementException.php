<?php

namespace Cozy\Database\Relational\Exceptions;

class StatementException extends Exception
{
    /** @var string */
    protected $sentence;
    /** @var array */
    protected $errorInfo = [];

    /**
     * Construct the exception for the class Statement.
     *
     * @param string $sentence
     * @param string $message [optional] The Exception message to throw.
     * @param mixed $code [optional] The Exception code.
     * @param \Throwable $previous [optional] The previous throwable used for the exception chaining.
     * @param array $error_info
     */
    public function __construct(
        string $sentence,
        string $message,
        $code = null,
        array $error_info = null,
        \Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);

        if (isset($error_info)) {
            $this->errorInfo = $error_info;
        }

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
