<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\CartConditions;

use Exception;
use Igniter\Cart\CartCondition;
use Igniter\Cart\Facades\Cart;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Local\Facades\Location;
use Igniter\Local\Models\Location as LocationModel;
use IgniterLabs\Shipday\Classes\Client;
use IgniterLabs\Shipday\Models\Settings;
use Override;

class OnDemandDelivery extends CartCondition
{
    public ?int $priority = 200;

    protected float $deliveryCharge = 0;

    protected static ?array $deliveryService = null;

    protected static bool $hasErrors = false;

    public static function getDeliveryService(): ?array
    {
        return static::$deliveryService ?: null;
    }

    public static function clearInternalCache(): void
    {
        self::$hasErrors = false;
        self::$deliveryService = null;
    }

    #[Override]
    public function getRules(): array
    {
        return [
            $this->deliveryCharge.' >= 0',
        ];
    }

    #[Override]
    public function beforeApply(): bool
    {
        // Do not apply condition when extension settings are not configured
        if (!Settings::supportsOnDemandDelivery()) {
            return false;
        }

        // Do not apply condition when orderType is not delivery
        if (Location::orderType() !== LocationModel::DELIVERY) {
            return false;
        }

        if (!Location::userPosition()->isValid()) {
            return false;
        }

        try {
            $estimateParams = $this->prepareEstimateRequest();
            static::$deliveryService ??= resolve(Client::class)->getAvailableService($estimateParams);

            $this->deliveryCharge = static::$deliveryService['fee']
                ?? Location::coveredArea()->deliveryAmount(Cart::subtotal());
        } catch (Exception $exception) {
            if (!self::$hasErrors) {
                flash()->alert($exception->getMessage())->now();
            }

            self::$hasErrors = true;

            return false;
        }

        return true;
    }

    #[Override]
    public function getActions(): array
    {
        return [
            ['value' => '+'.$this->deliveryCharge],
        ];
    }

    #[Override]
    public function getValue()
    {
        return $this->calculatedValue > 0 ? $this->calculatedValue : lang('igniter::main.text_free');
    }

    protected function prepareEstimateRequest(): array
    {
        $pickupAddress = format_address(Location::current()->getAddress(), false);
        if (empty($pickupAddress)) {
            throw new ApplicationException(lang('igniterlabs.shipday::default.alert_no_pickup_address'));
        }

        return [
            'pickup_address' => $pickupAddress,
            'delivery_address' => Location::userPosition()->getFormattedAddress() ?? '',
            'pickup_time' => Location::orderDateTime()->toIso8601String(),
        ];
    }

    public function __destruct()
    {
        static::clearInternalCache();
    }
}
