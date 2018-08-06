<?php

namespace Cozy\Database\Relational\Exceptions;

class Exception extends \RuntimeException
{
    /** @var mixed */
    protected $code;

    /**
     * Construct the base exception of this library.
     *
     * @param string $message [optional] The Exception message to throw.
     * @param mixed $code [optional] The Exception code.
     * @param \Throwable $previous [optional] The previous throwable used for the exception chaining.
     */
    public function __construct(string $message = '', $code = null, \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->code = $code;
    }
}
