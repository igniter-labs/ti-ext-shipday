<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Tests\Models;

use IgniterLabs\Shipday\Models\Settings;

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
