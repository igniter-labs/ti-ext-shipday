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

    public function getOnDemandServices(): array
    {
        if (Settings::getApiKey()) {
            try {
                //                $result = $this->sendRequest('on-demand/services', [], 'get');
                $result = [
                    [
                        'prod' => false,
                        'name' => 'DoorDash',
                        'status' => true,
                    ],
                ];
                if ($result) {
                    return collect($result)->map(fn($service) => $service['name'])->toArray();
                } else {
                    return [];
                }
            } catch (\Exception $e) {
                flash()->error(
                    lang('igniterlabs.shipday::default.alert_error_fetching_data', [
                        'error' => $e->getMessage(),
                    ])
                );

                return [];
            }
        }

        return [];
    }

    public function getOnDemandServicesAvailablility(): ?array
    {
//        return $this->sendRequest('on-demand/services/availability', [], 'get');
        sleep(1); // Simulate network delay
        return [
            [
                "id" => "1",
                "name" => "DoorDash",
                "fee" => 11.5,
                "pickupTime" => "2025-06-25T17:23:52Z",
                "deliveryTime" => "2025-06-25T18:16:41Z",
                "pickupDuration" => 11,
                "deliveryDuration" => 63,
                "error" => false,
                "errorCode" => null,
                "errorMessage" => null,
                "errorDescription" => null,
                "isProd" => false,
                "isInternal" => false,
                "probableAssignment" => false,
                "minBillableFee" => null,
                "regulatoryFee" => 0
            ],
            [
                "id" => null,
                "name" => "MotoClick",
                "fee" => null,
                "pickupTime" => null,
                "deliveryTime" => null,
                "pickupDuration" => null,
                "deliveryDuration" => null,
                "error" => true,
                "errorCode" => null,
                "errorMessage" => "No service available",
                "errorDescription" => "Outside Delivery Zone",
                "isProd" => false,
                "isInternal" => false,
                "probableAssignment" => false,
                "minBillableFee" => null,
                "regulatoryFee" => 0
            ],
            [
                "id" => "dqt_iEU4aT01QJGGHYYZubMqQA",
                "name" => "Uber",
                "fee" => 11.99,
                "pickupTime" => "2025-06-25T17:29:51Z",
                "deliveryTime" => "2025-06-25T18:40:29Z",
                "pickupDuration" => 17,
                "deliveryDuration" => 87,
                "error" => false,
                "errorCode" => null,
                "errorMessage" => null,
                "errorDescription" => null,
                "isProd" => false,
                "isInternal" => false,
                "probableAssignment" => false,
                "minBillableFee" => null,
                "regulatoryFee" => 0
            ]
        ];
    }

    public function estimateOnDemandDelivery(string $orderId): ?array
    {
        return $this->sendRequest('on-demand/estimate/' . $orderId, [], 'get');
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
