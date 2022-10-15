<?php

namespace IgniterLabs\Shipday\Actions;

use Admin\Models\Locations_model;
use Admin\Models\Staffs_model;
use Exception;
use Igniter\Flame\Database\Model;
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

    public function asShipdayDelivery()
    {
        if (!$this->hasShipdayDelivery()) {
            throw new Exception(class_basename($this).' is not a Shipday delivery yet. See the createAsShipdayDelivery method.');
        }

        return resolve(Client::class)->getOrder($this->shipdayId());
    }

    public function createAsShipdayDelivery(array $params = [])
    {
        if ($this->hasShipdayDelivery()) {
            throw new Exception(class_basename($this)." is already a Shipday delivery with ID {$this->shipdayId()}.");
        }

        if (!$this->model->isDeliveryType()) {
            throw new Exception(class_basename($this)." must be a delivery order.");
        }

        $params = $this->makeRequestParams($params);

        $response = resolve(Client::class)->insertOrder($params);

        $this->storeShipdayDelivery($response, $params);

        return $response;
    }

    protected function storeShipdayDelivery($response, $request)
    {
        $delivery = $this->model->shipday_delivery()->updateOrCreate([
            'order_id' => $this->model->order_id,
            'shipday_order_id' => $response['orderId'],
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

    public function updateShipdayDelivery(array $params = [])
    {
        return resolve(Client::class)->editOrder(
            $this->shipdayId(),
            $this->makeRequestParams($params),
        );
    }

    public function assignShipdayDeliveryToCarrier(Staffs_model $carrier)
    {
        $carrier->createOrGetShipdayCarrier();

        return resolve(Client::class)->assignOrder(
            $this->shipdayId(),
            $carrier->shipdayId(),
        );
    }

    protected function makeRequestParams(array $params = [])
    {
        $params['orderNumber'] = $this->model->order_id;
        $params['customerName'] = $this->model->customer_name;
        $params['customerAddress'] = $this->model->address->formatted_address;
        $params['customerEmail'] = $this->model->email;
        $params['customerPhoneNumber'] = $this->model->telephone;
        $params['restaurantName'] = $this->model->location->getName();
        $params['restaurantAddress'] = format_address($this->model->location->getAddress(), false);
        $params['restaurantPhoneNumber'] = $this->model->location->getTelephone();
        $params['deliveryFee'] = $this->model->getOrderTotals()->firstWhere('code', Locations_model::DELIVERY)->value ?? 0;

        return $params;
    }
}
