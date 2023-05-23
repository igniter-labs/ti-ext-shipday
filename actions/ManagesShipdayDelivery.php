<?php

namespace IgniterLabs\Shipday\Actions;

use Admin\Models\Locations_model;
use Admin\Models\Staffs_model;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\SystemException;
use IgniterLabs\Shipday\Classes\Client;
use IgniterLabs\Shipday\Models\DeliveryLog;
use System\Actions\ModelAction;

class ManagesShipdayDelivery extends ModelAction
{
    public function __construct(Model $model)
    {
        parent::__construct($model);

        $this->model->relation['hasMany']['shipday_logs'] = [DeliveryLog::class, 'delete' => true];

        $this->model->bindEvent('model.mailGetData', function () {
            if (!$this->model->hasShipdayDelivery())
                return [];

            return [
                'shipday_id' => $this->shipdayId(),
                'shipday_tracking_url' => array_get($this->model->asShipdayDelivery(), 'trackingLink'),
            ];
        });
    }

    public function shipdayId()
    {
        return $this->model->shipday_id;
    }

    public function shipdayOrderNumber()
    {
        return $this->model->order_id;
    }

    public function createOrGetShipdayDelivery()
    {
        if ($this->hasShipdayDelivery())
            return $this->asShipdayDelivery();

        return $this->createAsShipdayDelivery();
    }

    public function hasShipdayDelivery()
    {
        return !is_null($this->model->shipday_id);
    }

    public function assertShipdayDelivery()
    {
        if (!$this->hasShipdayDelivery()) {
            throw new SystemException("Order ID {$this->model->getKey()} is not a Shipday delivery yet. See the createAsShipdayDelivery method.");
        }
    }

    public function asShipdayDelivery()
    {
        $this->assertShipdayDelivery();

        $orders = resolve(Client::class)->getOrder($this->shipdayOrderNumber());

        return collect($orders)->firstWhere('orderId', $this->shipdayId());
    }

    public function createAsShipdayDelivery(array $params = [])
    {
        if ($this->hasShipdayDelivery())
            throw new SystemException("Order ID {$this->model->getKey()} is already a Shipday delivery with ID {$this->shipdayId()}.");

        if (!$this->model->isDeliveryType())
            throw new SystemException("Order ID {$this->model->getKey()} must be a delivery order.");

        $params = $this->makeRequestParams($params);

        $response = resolve(Client::class)->insertOrder($params);
        $this->model->shipday_id = $response['orderId'];
        $this->model->save();

        $this->logShipdayDelivery($response, $params);

        return $response;
    }

    public function editShipdayDelivery(array $params = [])
    {
        $this->assertShipdayDelivery();

        return resolve(Client::class)->editOrder(
            $this->shipdayId(),
            $this->makeRequestParams($params),
        );
    }

    public function updateShipdayDeliveryStatus($shipdayStatus)
    {
        $this->assertShipdayDelivery();

        $response = resolve(Client::class)->updateOrderStatus($this->shipdayId(), $shipdayStatus);

        if (!$response || !array_get($response, 'success'))
            throw new SystemException("Unable to update Shipday delivery status to {$shipdayStatus}.");

        return $this->logShipdayDelivery($response, ['status' => $shipdayStatus]);
    }

    public function logShipdayDelivery(array $response = [], array $request = [])
    {
        $response['shipday_order_details'] = $this->asShipdayDelivery();

        return DeliveryLog::logUpdate($this->model, $response, $request);
    }

    public function markShipdayDeliveryAsReadyForPickup()
    {
        $this->assertShipdayDelivery();

        resolve(Client::class)->readyForPickup($this->shipdayId());
    }

    public function assignShipdayDeliveryToDriver(Staffs_model $driver)
    {
        $this->assertShipdayDelivery();

        $driver->assertShipdayDriver();

        return rescue(function () use ($driver) {
            return resolve(Client::class)->assignOrder($this->shipdayId(), $driver->shipdayId());
        });
    }

    protected function makeRequestParams(array $params = [])
    {
        $orderTotals = $this->model->getOrderTotals()->keyBy('code');

        $orderDateTime = $this->model->order_date_time->tz('UTC');

        $params['orderNumber'] = $this->shipdayOrderNumber();
        $params['orderSource'] = $this->model->location->getName();
        $params['expectedDeliveryDate'] = $orderDateTime->toDateString();
        $params['expectedDeliveryTime'] = $orderDateTime->toTimeString();
        $params['expectedPickupTime'] = $orderDateTime->clone()->subMinutes(
            $this->model->location->getDeliveryWaitTime()
        )->toTimeString();

        $params['customerName'] = $this->model->customer_name;
        $params['customerAddress'] = $this->model->address->formatted_address;
        $params['customerEmail'] = $this->model->email;
        $params['customerPhoneNumber'] = $this->model->telephone;

        $params['restaurantName'] = $this->model->location->getName();
        $params['restaurantAddress'] = format_address($this->model->location->getAddress(), false);
        $params['restaurantPhoneNumber'] = $this->model->location->getTelephone();

        if ($this->model->location->location_lat)
            $params['pickupLatitude'] = $this->model->location->location_lat;

        if ($this->model->location->location_lng)
            $params['pickupLongitude'] = $this->model->location->location_lng;

        $params['deliveryInstruction'] = $this->model->comment;
        $params['paymentMethod'] = $this->model->payment == 'cod' ? 'cash' : 'credit_card';

        $params['deliveryFee'] = $orderTotals->get(Locations_model::DELIVERY)->value ?? 0;
        $params['tips'] = ($tip = $orderTotals->get('tip')) ? $tip->value : 0;
        $params['tax'] = ($tax = $orderTotals->get('tax')) ? $tax->value : 0;
        $params['discountAmount'] = ($coupon = $orderTotals->get('coupon')) ? trim($coupon->value, '-') : 0;
        $params['totalOrderCost'] = $orderTotals->get('total')->value;

        $params['orderItem'] = $this->model->getOrderMenusWithOptions()->map(function ($item) {
            return [
                'name' => $item->name,
                'unitPrice' => $item->price,
                'quantity' => $item->quantity,
                'detail' => $item->subtotal,
                'addOns' => $item->menu_options->map(function ($itemOption) {
                    return $itemOption->order_option_name.' ('
                        .currency_format($itemOption->quantity * $itemOption->order_option_price)
                        .') x '.$itemOption->quantity;
                })->all(),
            ];
        })->all();

        $eventResult = $this->model->fireSystemEvent('shipday.extendRequestParams', [$params], false);
        if (is_array($eventResult))
            $params = array_merge_recursive($params, ...array_filter($eventResult));

        return $params;
    }
}
