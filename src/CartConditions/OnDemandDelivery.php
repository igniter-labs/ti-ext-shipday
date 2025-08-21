<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\CartConditions;

use Exception;
use Igniter\Cart\CartCondition;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Local\Models\Location as LocationModel;
use Igniter\Local\Facades\Location;
use IgniterLabs\Shipday\Classes\Client;
use IgniterLabs\Shipday\Models\Settings;
use Override;

class OnDemandDelivery extends CartCondition
{
    public ?int $priority = 200;

    protected float $deliveryCharge = 0;

    protected static array $deliveryService = [];

    protected static bool $hasErrors = false;

    public static function getDeliveryService(): ?array
    {
        return static::$deliveryService ?: null;
    }

    #[Override]
    public function beforeApply()
    {
        // Do not apply condition when extension settings are not configured
        if (! Settings::supportsOnDemandDelivery()) {
            return false;
        }

        // Do not apply condition when orderType is not delivery
        if (Location::orderType() !== LocationModel::DELIVERY) {
            return false;
        }

        $estimateParams = $this->prepareEstimateRequest();
        if ($estimateParams['delivery_address'] === '') {
            return false;
        }

        try {
            static::$deliveryService = resolve(Client::class)->getAvailableService(
                $estimateParams['pickup_address'],
                $estimateParams['delivery_address'],
                $estimateParams['pickup_time']
            );
            if (static::$deliveryService) {
                $this->deliveryCharge = static::$deliveryService['fee'] ?? 0;
            } else {
                throw new ApplicationException(lang('igniterlabs.shipday::default.alert_no_delivery_service_available'));
            }
        } catch (Exception $exception) {
            if (!self::$hasErrors) {
                flash()->alert($exception->getMessage())->now();
            }

            self::$hasErrors = true;

            return false;
        }

        return true;
    }

    protected function prepareEstimateRequest(): array
    {
        $pickupAddress = format_address(LocationModel::getDefault()->getAddress(), false);
        if (empty($pickupAddress)) {
            throw new ApplicationException(lang('igniterlabs.shipday::default.alert_no_pickup_address'));
        }
        $deliveryAddress = Location::userPosition()->getFormattedAddress() ?? '';

        $pickupTime = Location::orderDateTime()->toIso8601String();
        if (empty($pickupTime)) {
            throw new ApplicationException(lang('igniterlabs.shipday::default.alert_no_pickup_time'));
        }

        return [
            'pickup_address' => $pickupAddress,
            'delivery_address' => $deliveryAddress,
            'pickup_time' => $pickupTime,
        ];
    }

    #[Override]
    public function getActions(): array
    {
        return [
            ['value' => "+$this->deliveryCharge"]
        ];
    }
    public static function clearInternalCache(): void
    {
        self::$hasErrors = false;
    }

    public function __destruct()
    {
        static::clearInternalCache();
    }
}
