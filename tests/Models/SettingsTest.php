<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Tests\Models;

use IgniterLabs\Shipday\Models\Settings;
use Illuminate\Support\Facades\Http;

beforeEach(function(): void {
    Settings::flushEventListeners();
});

it('returns true when webhook token is valid', function(): void {
    Settings::set('webhook_token', 'valid_token');

    expect(Settings::validateWebhookToken('valid_token'))->toBeTrue();
});

it('returns false when webhook token is invalid', function(): void {
    Settings::set([
        'webhook_token' => 'valid_token',
    ]);

    expect(Settings::validateWebhookToken('invalid_token'))->toBeFalse();
});

it('creates a webhook token when none exists', function(): void {
    Settings::clearInternalCache();
    expect(Settings::get('webhook_token'))->not->toBeNull();
});

it('returns correct status options for Shipday', function(): void {
    $statusOptions = Settings::getShipdayStatusOptions();

    expect($statusOptions->keys())->toContain(
        'ORDER_ACCEPTED_AND_STARTED',
        'STARTED',
        'PICKED_UP',
        'READY_TO_DELIVER',
        'ALREADY_DELIVERED',
        'INCOMPLETE',
        'FAILED_DELIVERY',
    );
});

it('returns true when on-demand delivery is supported', function(): void {
    Settings::set([
        'delivery_type' => 'on_demand',
        'api_key' => 'test-key',
    ]);

    expect(Settings::supportsOnDemandDelivery())->toBeTrue();
});

it('returns false when delivery type is not on-demand', function(): void {
    Settings::set([
        'delivery_type' => 'in_house',
        'api_key' => 'test-key',
    ]);

    expect(Settings::supportsOnDemandDelivery())->toBeFalse();
});

it('returns false when api key is not set', function(): void {
    Settings::set([
        'delivery_type' => 'on_demand',
        'api_key' => null,
    ]);

    expect(Settings::supportsOnDemandDelivery())->toBeFalse();
});

it('gets delivery service options', function(): void {
    Settings::set(['api_key' => 'test-key']);
    Http::fake([
        'https://api.shipday.com/on-demand/services' => Http::response([
            ['name' => 'DoorDash'],
            ['name' => 'Postmates'],
        ]),
    ]);

    $options = Settings::getDeliveryServiceOptions();

    expect($options)->toBeArray()
        ->and($options)->toHaveKey('DoorDash')
        ->and($options['DoorDash'])->toBe('DoorDash');
});
