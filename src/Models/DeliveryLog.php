<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Models;

use Igniter\Cart\Models\Order;
use Igniter\Flame\Database\Model;
use Igniter\User\Models\User;
use Illuminate\Support\Carbon;

/**
 * DeliveryLog Model
 *
 * @property int $id
 * @property int $order_id
 * @property string $shipday_id
 * @property int $fee
 * @property string $status
 * @property int $carrier_id
 * @property string $tracking_url
 * @property array $request_data
 * @property array $response_data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
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
            'order' => [Order::class, 'key' => 'order_id'],
            'carrier' => [User::class, 'key' => 'carrier_id', 'otherKey' => 'shipday_id'],
        ],
    ];

    protected $appends = ['created_since', 'carrier_name'];

    public static function logUpdate($model, array $response = [], array $request = []): static
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
        $model->saveQuietly();

        return $record;
    }

    public function getCreatedSinceAttribute($value): ?string
    {
        return $this->created_at ? time_elapsed($this->created_at) : null;
    }

    public function getCarrierNameAttribute()
    {
        return $this->carrier?->full_name;
    }

    public function isCancelled(): bool
    {
        return $this->status === 'FAILED_DELIVERY';
    }
}
