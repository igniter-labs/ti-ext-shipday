<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Exceptions;

use Exception;

class ClientException extends Exception
{
    public function __construct(public $response, protected $code = 0, ?Exception $previous = null)
    {
        $this->message = '[Shipday]: ';

        $this->message .= array_get($this->response, 'errorMessage') ?: 'An error occurred while communicating with shipday';
    }
}
