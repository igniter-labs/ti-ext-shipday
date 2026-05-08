<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Classes;

use Exception;
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
                $result = $this->sendRequest('on-demand/services', [], 'get');

                return collect((array)$result)
                    ->mapWithKeys(fn($service): array => [$service['name'] => $service['name']])
                    ->toArray();
            } catch (Exception $e) {
                flash()->error(
                    lang('igniterlabs.shipday::default.alert_error_fetching_data', [
                        'error' => $e->getMessage(),
                    ])
                );
            }
        }

        return [];
    }

    public function getAvailableService(array $params): ?array
    {
        $services = $this->sendRequest('on-demand/services/availability', $params);

        $onDemandTypeOption = Settings::get('on_demand_type_option');

        if ($onDemandTypeOption === 'manual_selection' && $deliveryService = Settings::getDeliveryService()) {
            return $this->getDeliveryServiceByName($services, $deliveryService);
        } elseif ($onDemandTypeOption === 'auto_select_lowest_fee') {
            return $this->getDeliveryServiceByLowestFee($services);
        } elseif ($onDemandTypeOption === 'auto_select_highest_fee') {
            return $this->getDeliveryServiceByHighestFee($services);
        }

        return null;
    }

    protected function getDeliveryServiceByName(array $services, string $deliveryService): ?array
    {
        return collect($services)->first(function(array $service) use ($deliveryService) {
            if ($service['name'] === $deliveryService && (bool)$service['error'] === false) {
                return $service;
            }
        });
    }

    protected function getDeliveryServiceByLowestFee(array $services): ?array
    {
        return collect($services)
            ->filter(fn(array $service): bool => !array_get($service, 'error'))
            ->sortBy('fee')->first();
    }

    protected function getDeliveryServiceByHighestFee(?array $services): ?array
    {
        return collect($services)
            ->filter(fn(array $service): bool => !array_get($service, 'error'))
            ->sortByDesc('fee')->first();
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

    public function cancelOnDemandDelivery(int|string $uuid): ?array
    {
        return $this->sendRequest('on-demand/cancel/'.$uuid);
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
