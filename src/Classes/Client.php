<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Classes;

use IgniterLabs\Shipday\Exceptions\ClientException;
use IgniterLabs\Shipday\Models\Settings;
use Illuminate\Support\Facades\Http;

class Client
{
    protected array $carriersCache = [];

    protected string $endpoint = 'https://api.shipday.com/';

    public function getOrder(int|string $uuid): ?array
    {
        return $this->sendRequest('orders/'.$uuid, [], 'get');
    }

    public function insertOrder(array $params): ?array
    {
        return $this->sendRequest('orders', $params);
    }

    public function editOrder(int|string $uuid, array $params): ?array
    {
        return $this->sendRequest('order/edit/'.$uuid, $params, 'put');
    }

    public function assignOrder(int|string $uuid, string $carrierId): ?array
    {
        return $this->sendRequest('orders/assign/'.$uuid.'/'.$carrierId, [], 'put');
    }

    public function deleteOrder(int|string $uuid): ?array
    {
        return $this->sendRequest('orders/'.$uuid, [], 'delete');
    }

    public function updateOrderStatus(int|string $uuid, $status): ?array
    {
        return $this->sendRequest('orders/'.$uuid.'/status', [
            'status' => $status,
        ], 'put');
    }

    public function readyForPickup(int|string $uuid): ?array
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
        if (!$this->carriersCache) {
            $this->carriersCache = $this->sendRequest('carriers', [], 'get');
        }

        return collect($this->carriersCache)->firstWhere('email', $email);
    }

    public function createCarrier(array $params): ?array
    {
        return $this->sendRequest('carriers', $params);
    }

    protected function sendRequest(string $uri, $data = [], $method = 'post'): ?array
    {
        $http = Http::withToken(Settings::getApiKey(), 'Basic');
        $http->withHeader('x-api-key', Settings::getApiKey());

        if ($method !== 'get') {
            $http->asJson();
        } else {
            $http->acceptJson();
        }

        $response = $http->$method($this->endpoint.$uri, $data);

        if (!$response->successful()) {
            throw new ClientException($response->json());
        }

        return $response->json();
    }
}
