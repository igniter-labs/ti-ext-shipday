<?php

namespace IgniterLabs\Shipday\Classes;

use IgniterLabs\Shipday\Exceptions\ClientException;
use IgniterLabs\Shipday\Models\Settings;
use Illuminate\Support\Facades\Http;

class Client
{
    protected static $carriersCache = [];

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

    public function updateOrderStatus($uuid, $status)
    {
        return $this->sendRequest('orders/'.$uuid.'/status', [
            'status' => $status,
        ], 'put');
    }

    public function readyForPickup($uuid)
    {
        return $this->sendRequest('orders/'.$uuid.'/meta', [
            'readyToPickup' => true,
        ], 'put');
    }

    //
    //
    //

    public function getCarrier($email)
    {
        if (!self::$carriersCache)
            self::$carriersCache = $this->sendRequest('carriers', [], 'get');

        return collect(self::$carriersCache)->firstWhere('email', $email);
    }

    public function createCarrier(array $params)
    {
        return $this->sendRequest('carriers', $params);
    }

    protected function sendRequest($uri, $data = [], $method = 'post'): ?array
    {
        $http = Http::withToken(Settings::getApiKey(), 'Basic');

        if ($method !== 'get') {
            $http->asJson();
        }
        else {
            $http->acceptJson();
        }

        $response = $http->$method($this->endpoint.$uri, $data);

        if (!$response->successful()) {
            throw new ClientException($response->json());
        }

        return $response->json();
    }
}
