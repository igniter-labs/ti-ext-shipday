<?php

namespace IgniterLabs\Shipday;

use Igniter\Cart\Models\Orders_model;
use IgniterLabs\Shipday\Actions\ManagesShipdayCarrier;
use IgniterLabs\Shipday\Actions\ManagesShipdayDelivery;
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
            $model->implement[] = ManagesShipdayCarrier::class;
        });

        Event::listen('admin.order.paymentProcessed', function ($order) {
            if ($order->isDeliveryType()) $order->createOrGetShipdayDelivery();
        });

        Event::listen('admin.assignable.assigned', function ($model, $assignableLog) {
            if ($model instanceof Orders_model
                && Settings::canAssignGroup($assignableLog->assignee_group_id)
                && $assignableLog->assignee
                && $model->isDeliveryType()
            ) {
                $model->assignShipdayDeliveryToCarrier($assignableLog->assignee);
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

    public function registerCartConditions()
    {
        return [
            \IgniterLabs\Shipday\CartConditions\Shipday::class => [
                'name' => 'shipday',
                'label' => 'lang:igniterlabs.shipday::default.label_cart_condition_title',
                'description' => 'lang:igniterlabs.shipday::default.label_cart_condition_description',
            ],
        ];
    }
}
