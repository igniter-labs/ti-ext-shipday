<?php

namespace IgniterLabs\Shipday;

use System\Classes\BaseExtension;

/**
 * Shipday Extension Information File
 */
class Extension extends BaseExtension
{
    /**
     * Register method, called when the extension is first registered.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
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
