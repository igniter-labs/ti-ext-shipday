<?php

namespace IgniterLabs\Shipday;

use Igniter\System\Classes\BaseExtension;
use IgniterLabs\Shipday\Classes\Client;
use IgniterLabs\Shipday\Classes\EventRegistry;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

/**
 * Shipday Extension Information File
 */
class Extension extends BaseExtension
{
    public $singletons = [
        Client::class,
        EventRegistry::class,
    ];

    public function boot()
    {
        resolve(EventRegistry::class)->boot();

        VerifyCsrfToken::except([
            'shipday/webhook/*',
        ]);
    }

    /**
     * Registers any admin permissions used by this extension.
     *
     * @return array
     */
    public function registerPermissions(): array
    {
        return [
            'IgniterLabs.Shipday.ManageSettings' => [
                'description' => 'lang:igniterlabs.shipday::default.help_permission',
                'group' => 'module',
            ],
        ];
    }

    public function registerSettings(): array
    {
        return [
            'settings' => [
                'label' => 'lang:igniterlabs.shipday::default.text_settings',
                'description' => 'lang:igniterlabs.shipday::default.help_settings',
                'icon' => 'fa fa-truck',
                'model' => \IgniterLabs\Shipday\Models\Settings::class,
                'permissions' => ['IgniterLabs.Shipday.ManageSettings'],
            ],
        ];
    }
}
