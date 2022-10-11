<?php

namespace IgniterLabs\Shipday\Classes;

use IgniterLabs\DoorDashDrive\Exceptions\ClientException;
use Illuminate\Support\Facades\Http;

class Client
{
    protected $endpoint = 'https://api.shipday.com/';

    protected $apiKey;

    public function __construct($key)
    {
        $this->apiKey = $key;
    }

    public function getOrder($uuid)
    {
        return $this->sendRequest('orders/'.$uuid);
    }

    public function insertOrder(array $params)
    {
        return $this->sendRequest('orders', $params);
    }

    public function editOrder($uuid, array $params)
    {
        return $this->sendRequest('order/edit/'.$uuid, $params);
    }

    public function assignOrder($uuid, $carrierId)
    {
        return $this->sendRequest('orders/assign/'.$uuid.'/'.$carrierId);
    }

    public function deleteOrder($uuid)
    {
        return $this->sendRequest('deliveries/'.$uuid, $params, 'patch');
    }

    protected function sendRequest($uri, $data = [], $method = 'post'): array
    {
        $response = Http::withToken($this->token)
            ->acceptJson()
            ->withoutRedirecting()
            ->$method($this->endpoint.$uri, $data);

        if (!$response->ok()) {
            throw new ClientException($response->json());
        }

        return $response->json();
    }

}
