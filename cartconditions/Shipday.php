<?php

namespace IgniterLabs\Shipday\CartConditions;

use Igniter\Flame\Cart\CartCondition;
use IgniterLabs\Shipday\Models\Delivery;

class Shipday extends CartCondition
{
    public $priority = 200;

    protected $deliveryValue = 0;

    public function getModel()
    {
    }

    public function onLoad()
    {
        try {
            if (!strlen($deliveryId = session()->get('shipday_delivery_id')))
                return;

            $delivery = Delivery::find($deliveryId);

            if (!is_null($delivery) && !is_null($delivery->estimate_order_id)) {
                $this->deliveryValue = $delivery->fee;
            }
        }
        catch (\Exception $ex) {
            flash()->alert($ex->getMessage())->now();
            $this->removeMetaData('code');
        }
    }

    public function beforeApply()
    {
        if (!$this->deliveryValue)
            return false;
    }

    public function getActions()
    {
        return [
            ['value' => "+{$this->deliveryValue}"],
        ];
    }
}
