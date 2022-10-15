<?php

namespace IgniterLabs\Shipday\Classes;

use IgniterLabs\Shipday\Exceptions\ClientException;
use IgniterLabs\Shipday\Models\Settings;
use Illuminate\Support\Facades\Http;

class Client
{
    protected $endpoint = 'https://api.shipday.com/';

    public function getOrder($uuid)
    {
        return $this->sendRequest('orders/'.$uuid, [], 'get');
    }

    public function insertOrder(array $params)
    {
        return $this->sendRequest('orders', $params);
    }

    public function editOrder($uuid, array $params)
    {
        return $this->sendRequest('order/edit/'.$uuid, $params, 'put');
    }

    public function assignOrder($uuid, $carrierId)
    {
        return $this->sendRequest('orders/assign/'.$uuid.'/'.$carrierId, [], 'put');
    }

    public function deleteOrder($uuid)
    {
        return $this->sendRequest('orders/'.$uuid, [], 'delete');
    }

    //
    //
    //

    public function getCarrier($id)
    {
        $carriers = $this->sendRequest('carriers', [], 'get');

        return collect($carriers)->firstWhere('id', $id);
    }

    public function createCarrier(array $params)
    {
        return $this->sendRequest('carriers', $params);
    }

    protected function sendRequest($uri, $data = [], $method = 'post'): array
    {
        $response = Http::withToken(Settings::getApiKey(), 'Basic')
            ->acceptJson()
            ->withoutRedirecting()
            ->$method($this->endpoint.$uri, $data);

        if (!$response->successful()) {
            throw new ClientException($response->json());
        }

        return $response->json();
    }

}
