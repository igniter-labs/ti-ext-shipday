<?php

namespace IgniterLabs\DoorDashDrive\Exceptions;

class ClientException extends \Exception
{
    public $response;

    public function __construct($response, $code = 0, \Exception $previous = null)
    {
        $this->response = $response;

        $this->message = '[DoorDash Drive]: ';

        if (array_get($response, 'code') === 'invalid_delivery_parameters') {
            $this->message .= lang(array_get([
                'distance_too_long' => 'igniterlabs.doordashdrive::default.alert_distance_too_long',
                'delivery_address_not_in_coverage' => 'igniterlabs.doordashdrive::default.alert_delivery_address_not_in_coverage',
                'outside_of_delivery_time' => 'igniterlabs.doordashdrive::default.alert_outside_of_delivery_time',
            ], array_get($response, 'reason', '')));
        }
        elseif ($errors = array_get($response, 'field_errors')) {
            $this->message .= collect($errors)->pluck('error')->implode(', ');
        }
        else {
            $this->message .= array_get($response, 'message') ?: 'An error occurred while requesting a delivery fee';
        }
    }

    public function isValidationError()
    {
        return array_get($this->response, 'code') === 'validation_error';
    }
}
