<?php

namespace IgniterLabs\Shipday\Models;

use Igniter\Flame\Database\Model;

class Delivery extends Model
{
    public $table = 'igniterlabs_shipday_deliveries';

    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    public $relation = [
        'belongsTo' => [
            'order' => ['Admin\Models\Orders_model', 'key' => 'order_id'],
        ],
    ];

    public function fillFromRemote(array $response)
    {
        $this->status = array_get($response, 'orderStatus.orderState', 'SENT');
        $this->response_data = $response;

        return $this;
    }

    public function updateFromWebhook(array $response)
    {
        $this->status = array_get($response, 'order_status', 'SENT');
        $this->tracking_url = array_get($response, 'order.tracking_url');
        $this->response_data = $response;
        $this->save();

        if ($this->order && ($statusId = Settings::getOrderStatusIdByShipdayStatus($this->status))) {
            $this->order->updateOrderStatus($statusId, ['notify' => false]);
        }

        return $this;
    }

    public function isReadyForPickup()
    {
        return in_array($this->status, ['NOT_ASSIGNED', 'NOT_ACCEPTED', 'NOT_STARTED_YET']);
    }
}
