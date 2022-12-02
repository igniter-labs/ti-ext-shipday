<?php

namespace IgniterLabs\Shipday\Models;

use Igniter\Flame\Database\Model;

class DeliveryLog extends Model
{
    public $table = 'igniterlabs_shipday_logs';

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

    public static function logUpdate($model, array $response = [], array $request = [])
    {
        $record = new static;
        $record->order_id = $model->shipdayOrderNumber();
        $record->shipday_id = array_get($response, 'orderId', array_get($response, 'order.id'));
        $record->fee = array_get($request, 'deliveryFee');
        $record->status = array_get($response, 'orderStatus.orderState', array_get($response, 'order_status', 'SENT'));
        $record->tracking_url = array_get($response, 'shipday_order_details.trackingLink');
        $record->carrier_id = array_get($response, 'assignedCarrier.id', array_get($response, 'carrier.id'));
        $record->request_data = $request;
        $record->response_data = $response;

        $record->save();

        $model->shipday_id = $record->isCancelled() ? null : $record->shipday_id;
        $model->save();

        return $record;
    }

    public function getCreatedSinceAttribute($value)
    {
        return $this->created_at ? time_elapsed($this->created_at) : null;
    }

    public function isCancelled()
    {
        return $this->status === 'FAILED_DELIVERY';
    }
}
