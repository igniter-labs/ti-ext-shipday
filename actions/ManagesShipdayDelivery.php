<?php

namespace IgniterLabs\Shipday\Actions;

use Admin\Models\Locations_model;
use Admin\Models\Staffs_model;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Traits\ExtensionTrait;
use IgniterLabs\Shipday\Classes\Client;
use IgniterLabs\Shipday\Models\Delivery;
use System\Actions\ModelAction;

class ManagesShipdayDelivery extends ModelAction
{
    use ExtensionTrait;

    public function __construct(Model $model)
    {
        parent::__construct($model);

        $this->model->relation['hasOne']['shipday_delivery'] = [Delivery::class, 'delete' => true];
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

        $this->storeShipdayDelivery($response, $params);

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

    public function updateAsShipdayDelivery()
    {
        if (!$response = $this->asShipdayDelivery())
            throw new SystemException('Unable to update Shipday delivery, shipday order not found');

        $delivery = $this->model->shipday_delivery;
        $delivery->fillFromRemote($response)->save();

        $this->model->shipday_id = $delivery->isCancelled() ? null : $response['orderId'];
        $this->model->save();

        return $delivery;
    }

    public function updateShipdayDeliveryStatus($shipdayStatus)
    {
        $this->assertShipdayDelivery();

        $response = resolve(Client::class)->updateOrderStatus($this->shipdayId(), $shipdayStatus);

        if (!$response || !array_get($response, 'success'))
            throw new SystemException("Unable to update Shipday delivery status to {$shipdayStatus}.");

        return $this->updateAsShipdayDelivery();
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
            return resolve(Client::class)->assignOrder($this->shipdayId(), $driver->shipday_id);
        });
    }

    protected function storeShipdayDelivery($response, $request)
    {
        $delivery = $this->model->shipday_delivery()->updateOrCreate([
            'order_id' => $this->shipdayOrderNumber(),
            'shipday_id' => $response['orderId'],
        ], [
            'fee' => array_get($request, 'deliveryFee'),
            'status' => array_get($response, 'orderStatus.orderState', 'SENT'),
            'request_data' => $request,
            'response_data' => $response,
        ]);

        $this->model->shipday_id = $response['orderId'];
        $this->model->save();

        $this->model->shipday_delivery = $delivery;

        return $this;
    }

    protected function makeRequestParams(array $params = [])
    {
        $orderTotals = $this->model->getOrderTotals()->keyBy('code');

        $params['orderNumber'] = $this->shipdayOrderNumber();
        $params['orderSource'] = 'TastyIgniter';

        $params['customerName'] = $this->model->customer_name;
        $params['customerAddress'] = $this->model->address->formatted_address;
        $params['customerEmail'] = $this->model->email;
        $params['customerPhoneNumber'] = $this->model->telephone;

        $params['restaurantName'] = $this->model->location->getName();
        $params['restaurantAddress'] = format_address($this->model->location->getAddress(), false);
        $params['restaurantPhoneNumber'] = $this->model->location->getTelephone();

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

        return $params;
    }
}
