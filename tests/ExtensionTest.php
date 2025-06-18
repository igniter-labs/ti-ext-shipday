<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Tests;

use IgniterLabs\Shipday\Extension;
use IgniterLabs\Shipday\Models\Settings;

beforeEach(function(): void {
    $this->extension = new Extension(app());
});

it('registers settings', function(): void {
    $settings = $this->extension->registerSettings();

    expect($settings)->toHaveKey('settings')
        ->and($settings['settings']['model'])->toBe(Settings::class)
        ->and($settings['settings']['permissions'])->toBe(['IgniterLabs.Shipday.ManageSettings']);
});

it('registers permissions', function(): void {
    $permissions = $this->extension->registerPermissions();

    expect($permissions)->toHaveKey('IgniterLabs.Shipday.ManageSettings')
        ->and($permissions['IgniterLabs.Shipday.ManageSettings']['group'])->toBe('igniter.cart::default.text_permission_menu_group');
});
