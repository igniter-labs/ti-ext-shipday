<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday;

use Igniter\System\Classes\BaseExtension;
use IgniterLabs\Shipday\Classes\Client;
use IgniterLabs\Shipday\Classes\EventRegistry;
use IgniterLabs\Shipday\Models\Settings;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Override;

/**
 * Shipday Extension Information File
 */
class Extension extends BaseExtension
{
    public $singletons = [
        Client::class,
        EventRegistry::class,
    ];

    #[Override]
    public function boot(): void
    {
        resolve(EventRegistry::class)->boot();

        VerifyCsrfToken::except([
            'shipday/webhook/*',
        ]);
    }

    /**
     * Registers any admin permissions used by this extension.
     */
    #[Override]
    public function registerPermissions(): array
    {
        return [
            'IgniterLabs.Shipday.ManageSettings' => [
                'description' => 'lang:igniterlabs.shipday::default.help_permission',
                'group' => 'igniter.cart::default.text_permission_menu_group',
            ],
        ];
    }

    #[Override]
    public function registerSettings(): array
    {
        return [
            'settings' => [
                'label' => 'lang:igniterlabs.shipday::default.text_settings',
                'description' => 'lang:igniterlabs.shipday::default.help_settings',
                'icon' => 'fa fa-truck',
                'model' => Settings::class,
                'permissions' => ['IgniterLabs.Shipday.ManageSettings'],
            ],
        ];
    }
}
