<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\System\Actions\SettingsModel;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static bool set(string|array $key, mixed $value = null)
 * @mixin SettingsModel
 */
class Settings extends Model
{
    public array $implement = [SettingsModel::class];

    // A unique code
    public $settingsCode = 'igniterlabs_shipday_settings';

    // Reference to field configuration
    public $settingsFieldsConfig = 'settings';

    protected static $apiKey;

    public static function isConnected(): bool
    {
        return Igniter::hasDatabase()
            && strlen((string) self::get('api_key'))
            && strlen((string) self::get('webhook_token'));
    }

    public static function validateWebhookToken(?string $token): bool
    {
        return $token === self::get('webhook_token');
    }

    public static function getApiKey()
    {
        return self::get('api_key');
    }

    public static function supportsOnDemandDelivery(): bool
    {
        return false;
    }

    public static function getShipdayStatusOptions()
    {
        return collect([
            'ORDER_ACCEPTED_AND_STARTED' => 'igniterlabs.shipday::default.label_accepted_status',
            'STARTED' => 'igniterlabs.shipday::default.label_started_status',
            'PICKED_UP' => 'igniterlabs.shipday::default.label_picked_up_status',
            'READY_TO_DELIVER' => 'igniterlabs.shipday::default.label_ready_to_deliver_status',
            'ALREADY_DELIVERED' => 'igniterlabs.shipday::default.label_delivered_status',
            'INCOMPLETE' => 'igniterlabs.shipday::default.label_incomplete_status',
            'FAILED_DELIVERY' => 'igniterlabs.shipday::default.label_failed_delivery_status',
        ]);
    }

    public static function isShipdayDriverStaffGroup($groupId): bool
    {
        return (int)self::get('delivery_staff_group') === $groupId;
    }

    public static function isReadyForPickupOrderStatus($statusId): bool
    {
        return $statusId == self::getReadyForPickupStatusId();
    }

    public static function getReadyForPickupStatusId()
    {
        return self::get('ready_for_pickup_status_id');
    }

    public static function getShipdayStatusMap()
    {
        return collect(self::get('status_map', []))->pluck('order_status', 'shipday_status');
    }

    public function getWebhookTokenAttribute()
    {
        if ($value = array_get($this->data, 'webhook_token')) {
            return $value;
        }

        self::set('webhook_token', $token = str_random(32));

        return $token;
    }
}
