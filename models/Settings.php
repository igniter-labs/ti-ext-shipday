<?php

namespace IgniterLabs\Shipday\Models;

use Igniter\Flame\Database\Model;

class Settings extends Model
{
    public $implement = [\System\Actions\SettingsModel::class];

    // A unique code
    public $settingsCode = 'igniterlabs_shipday_settings';

    // Reference to field configuration
    public $settingsFieldsConfig = 'settings';

    protected static $apiKey;

    public static function isConnected()
    {
        return self::isConfigured()
            && strlen(self::get('webhook_token'));
    }

    public static function validateWebhookToken(?string $token): bool
    {
        return $token === self::get('webhook_token');
    }

    public static function createAccessToken()
    {
        $header = json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
            'dd-ver' => 'DD-JWT-V1',
        ]);

        $payload = json_encode([
            'aud' => 'doordash',
            'iss' => self::getDeveloperId(),
            'kid' => self::getKeyId(),
            'exp' => time() + 60,
            'iat' => time(),
        ]);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payload);

        $base64UrlSignature = self::base64UrlEncode(hash_hmac('sha256',
            $base64UrlHeader.".".$base64UrlPayload,
            self::base64UrlDecode(self::getSigningSecret()),
            true
        ));

        return $base64UrlHeader.".".$base64UrlPayload.".".$base64UrlSignature;
    }

    public static function getApiKey()
    {
        return self::get('api_key');
    }

    public static function supportsOnDemandDelivery()
    {
        return false;
    }

    public static function isShipdayDriverStaffGroup($groupId)
    {
        return (int)self::get('delivery_staff_group') === $groupId;
    }

    public static function isReadyForPickupOrderStatus($statusId)
    {
        return $statusId == self::getReadyForPickupStatusId();
    }

    public static function getReadyForPickupStatusId()
    {
        return self::get('ready_for_pickup_status_id');
    }

    public static function getAcceptedStatusId()
    {
        return self::get('accepted_status_id');
    }

    public static function getPickedUpStatusId()
    {
        return self::get('picked_up_status_id');
    }

    public static function getCompletedStatusId()
    {
        return self::get('delivered_status_id');
    }

    public static function getCanceledStatusId()
    {
        return self::get('canceled_status_id');
    }

    public static function getShipdayStatusMap()
    {
        return collect([
            'ORDER_ACCEPTED_AND_STARTED' => self::getAcceptedStatusId(),
            'STARTED' => self::getAcceptedStatusId(),
            'PICKED_UP' => self::getPickedUpStatusId(),
            'ALREADY_DELIVERED' => self::getCompletedStatusId(),
            'ORDER_COMPLETED' => self::getCompletedStatusId(),
            'FAILED_DELIVERY' => self::getCanceledStatusId(),
        ])->filter();
    }

    public function getWebhookTokenAttribute($value)
    {
        if (strlen($value))
            return $value;

        self::set('webhook_token', $token = str_random(32));

        return $token;
    }

    protected static function base64UrlEncode(string $data): string
    {
        $base64Url = strtr(base64_encode($data), '+/', '-_');

        return rtrim($base64Url, '=');
    }

    protected static function base64UrlDecode(string $base64Url): string
    {
        return base64_decode(strtr($base64Url, '-_', '+/'));
    }
}
