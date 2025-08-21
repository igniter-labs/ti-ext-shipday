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

    public function assignOnDemandDeliveryService(array $params): ?array
    {
        return $this->sendRequest('on-demand/assign', $params);
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

    protected function fetchOnDemandServicesAvailability(array $params): ?array
    {
        return $this->sendRequest('on-demand/services/availability', $params);
    }

    public function getAvailableService(string $pickupAddress, string $deliveryAddress, string $pickupTime): ?array
    {
        $services = $this->fetchOnDemandServicesAvailability(compact('pickupAddress', 'deliveryAddress', 'pickupTime'));

        $onDemandTypeOption = Settings::get('on_demand_type_option');

        if ($onDemandTypeOption === 'manual_selection' && $deliveryService = Settings::get('delivery_service')) {
            return $this->getDeliveryServiceByName($services, $deliveryService);
        } else if ($onDemandTypeOption === 'auto_select_lowest_fee') {
            return $this->getDeliveryServiceByLowestFee($services);
        }
        return null;
    }

    protected function getDeliveryServiceByName(array $services, string $deliveryService): ?array
    {
        foreach ($services as $service) {
            if ($service['name'] === $deliveryService && (bool)$service["error"] === false) {
                return $service;
            }
        }
        return null;
    }

    protected function getDeliveryServiceByLowestFee(array $services): ?array
    {
        $lowestFeeService = null;

        foreach ($services as $service) {
            if ((bool)$service["error"] === true) {
                continue;
            }

            if (is_null($lowestFeeService) || $service['fee'] < $lowestFeeService['fee']) {
                $lowestFeeService = $service;
            }
        }

        return $lowestFeeService;
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
