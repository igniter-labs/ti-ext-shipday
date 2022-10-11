<?php

namespace IgniterLabs\Shipday\Models;

use Igniter\Flame\Database\Model;

class Delivery extends Model
{
    public $table = 'igniterlabs_shipday_deliveries';

    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'response_data' => 'array',
    ];

    public $relation = [
        'belongsTo' => [
            'order' => ['Admin\Models\Orders_model', 'key' => 'order_id'],
        ],
    ];

    public function fillFromQuote(array $quote)
    {
        $this->uuid = array_get($quote, 'external_delivery_id');
        $this->fee = array_get($quote, 'fee');
        $this->status = array_get($quote, 'delivery_status');
        $this->tracking_url = array_get($quote, 'tracking_url');
        $this->response_data = $quote;

        return $this;
    }

    public function markAsDelivered()
    {
        return $this->order->updateOrderStatus(Settings::getCompletedStatusId());
    }

    public function markAsCanceled()
    {
        return $this->order->updateOrderStatus(Settings::getCanceledStatusId());
    }
}
