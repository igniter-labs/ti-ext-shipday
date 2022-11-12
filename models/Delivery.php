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
        $this->status = array_get($response, 'orderStatus.orderState', $this->status);
        $this->tracking_url = array_get($response, 'order.tracking_url', $this->tracking_url);
        $this->response_data = $response;

        return $this;
    }

    public function isCancelled()
    {
        return $this->status === 'FAILED_DELIVERY';
    }
}
