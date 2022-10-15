<?php

namespace IgniterLabs\Shipday\CartConditions;

use Admin\Models\Locations_model;
use Igniter\Flame\Cart\CartCondition;
use Igniter\Local\Facades\Location;
use IgniterLabs\Shipday\Classes\Manager;

class Shipday extends CartCondition
{
    public $priority = 200;

    protected $deliveryValue = 0;

    protected static $hasException = false;

    public static $deliveryAddressChanged = false;

    public function getModel()
    {
    }

    public function beforeApply()
    {
        // Do not apply condition when orderType is not delivery
        if (Location::orderType() != Locations_model::DELIVERY)
            return false;

        try {
            if (!$deliveryFee = resolve(Manager::class)->getDeliveryFee())
                return false;

            $this->deliveryValue = $deliveryFee;
        }
        catch (\Exception $ex) {
            if (!self::$hasException)
                flash()->alert($ex->getMessage())->now();

            self::$hasException = true;
        }
    }

    public function getActions()
    {
        return [
            ['value' => "+{$this->deliveryValue}"],
        ];
    }
}
