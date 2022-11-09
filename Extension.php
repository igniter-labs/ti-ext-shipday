<?php

namespace IgniterLabs\Shipday;

use Admin\Models\Orders_model;
use IgniterLabs\Shipday\Actions\ManagesShipdayDelivery;
use IgniterLabs\Shipday\Actions\ManagesShipdayDriver;
use IgniterLabs\Shipday\Models\Settings;
use Illuminate\Support\Facades\Event;
use System\Classes\BaseExtension;

/**
 * Shipday Extension Information File
 */
class Extension extends BaseExtension
{
    public function boot()
    {
        \Admin\Models\Orders_model::extend(function ($model) {
            $model->implement[] = ManagesShipdayDelivery::class;
        });

        \Admin\Models\Staffs_model::extend(function ($model) {
            $model->implement[] = ManagesShipdayDriver::class;
        });

        Event::listen('admin.order.paymentProcessed', function ($order) {
            if (!Settings::supportsOnDemandDelivery() && $order->isDeliveryType())
                $order->createOrGetShipdayDelivery();
        });

        Event::listen('admin.statusHistory.beforeAddStatus', function ($model, $object, $statusId, $previousStatus) {
            if ($object instanceof Orders_model && Settings::isMappedShipdayStatus($statusId)) {
                $object->createOrGetShipdayDelivery();

                if (Settings::isReadyForPickupOrderStatus($statusId)) {
                    $object->markShipdayDeliveryAsReadyForPickup();
                }
                else {
                    $object->updateShipdayDeliveryStatus(
                        Settings::getShipdayStatusMap()->flip()->get($statusId)
                    );
                }
            }
        });

        Event::listen('admin.assignable.assigned', function ($model, $assignableLog) {
            if ($model instanceof Orders_model
                && $assignableLog->assignee
                && $model->isDeliveryType()
                && Settings::isShipdayDriverStaffGroup($assignableLog->assignee_group_id)
            ) {
                $model->createOrGetShipdayDelivery();
                $assignableLog->assignee->createOrGetShipdayDriver();

                $model->assignShipdayDeliveryToDriver($assignableLog->assignee);
            }
        });
    }

    /**
     * Registers any admin permissions used by this extension.
     *
     * @return array
     */
    public function registerPermissions()
    {
        // Remove this line and uncomment block to activate
        return [
            'IgniterLabs.Shipday.ManageSettings' => [
                'description' => 'lang:igniterlabs.shipday::default.help_permission',
                'group' => 'module',
            ],
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label' => 'lang:igniterlabs.shipday::default.text_settings',
                'description' => 'lang:igniterlabs.shipday::default.help_settings',
                'icon' => 'fa fa-gear',
                'model' => \IgniterLabs\Shipday\Models\Settings::class,
                'permissions' => ['IgniterLabs.Shipday.ManageSettings'],
            ],
        ];
    }
}
