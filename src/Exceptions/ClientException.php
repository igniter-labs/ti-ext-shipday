<?php

namespace IgniterLabs\Shipday\Exceptions;

class ClientException extends \Exception
{
    public $response;

    public function __construct($response, $code = 0, \Exception $previous = null)
    {
        $this->response = $response;

        $this->message = '[Shipday]: ';

        $this->message .= array_get($response, 'errorMessage') ?: 'An error occurred while communicating with shipday';
    }

    public function isValidationError()
    {
        return array_get($this->response, 'code') === 'validation_error';
    }
}
