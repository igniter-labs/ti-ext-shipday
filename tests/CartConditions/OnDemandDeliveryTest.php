<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Tests\CartConditions;

use Igniter\Cart\Facades\Cart;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Local\Classes\CoveredArea;
use Igniter\Local\Facades\Location;
use Igniter\Local\Models\Location as LocationModel;
use IgniterLabs\Shipday\CartConditions\OnDemandDelivery;
use IgniterLabs\Shipday\Models\Settings;
use Illuminate\Support\Facades\Http;

beforeEach(function(): void {
    OnDemandDelivery::clearInternalCache();
});

it('returns null delivery service when not set', function(): void {
    expect(OnDemandDelivery::getDeliveryService())->toBeNull();
});

it('does not apply when on-demand delivery is not supported', function(): void {
    Settings::set([
        'delivery_type' => 'in_house',
        'api_key' => null,
    ]);

    $condition = new OnDemandDelivery;

    expect($condition->beforeApply())->toBeFalse();
});

it('does not apply when order type is not delivery', function(): void {
    Settings::set([
        'delivery_type' => 'on_demand',
        'api_key' => 'test-key',
    ]);
    Location::shouldReceive('orderType')->andReturn('collection');

    $condition = new OnDemandDelivery;

    expect($condition->beforeApply())->toBeFalse();
});

it('does not apply when delivery address is empty', function(): void {
    Settings::set([
        'delivery_type' => 'on_demand',
        'api_key' => 'test-key',
    ]);
    $location = LocationModel::factory()->make();
    Location::shouldReceive('orderType')->andReturn(LocationModel::DELIVERY);
    Location::shouldReceive('current')->andReturn($location);
    Location::shouldReceive('userPosition')->andReturn(\Igniter\Flame\Geolite\Model\Location::createFromArray([]));
    Location::shouldReceive('orderDateTime')->andReturn(now());

    $condition = new OnDemandDelivery;

    expect($condition->beforeApply())->toBeFalse();
});

it('throws exception when pickup address is empty', function(): void {
    Settings::set([
        'delivery_type' => 'on_demand',
        'api_key' => 'test-key',
    ]);
    $location = new LocationModel;
    Location::shouldReceive('orderType')->andReturn(LocationModel::DELIVERY);
    Location::shouldReceive('current')->andReturn($location);

    $condition = new OnDemandDelivery;

    expect(fn(): bool => $condition->beforeApply())->toThrow(ApplicationException::class);
});

it('applies condition successfully when service is available with fee', function(): void {
    Settings::set([
        'delivery_type' => 'on_demand',
        'api_key' => 'test-key',
        'on_demand_type_option' => 'auto_select_lowest_fee',
    ]);
    Location::shouldReceive('orderType')->andReturn(LocationModel::DELIVERY);
    $location = LocationModel::factory()->make();
    Location::shouldReceive('current')->andReturn($location);
    Location::shouldReceive('userPosition')->andReturn(\Igniter\Flame\Geolite\Model\Location::createFromArray([
        'formattedAddress' => '456 Customer St',
    ]));
    Location::shouldReceive('orderDateTime')->andReturn(now());
    Http::fake([
        'https://api.shipday.com/on-demand/services/availability' => Http::response([
            ['name' => 'DoorDash', 'fee' => 3.50, 'error' => false],
        ]),
    ]);

    $condition = new OnDemandDelivery;
    $result = $condition->beforeApply();

    expect($result)->toBeTrue()
        ->and($condition->getActions())->toBe([['value' => '+3.5']])
        ->and(OnDemandDelivery::getDeliveryService())->toBeArray()
        ->and(OnDemandDelivery::getDeliveryService()['name'])->toBe('DoorDash');
});

it('uses covered area delivery amount when service fee is not available', function(): void {
    Settings::set([
        'delivery_type' => 'on_demand',
        'api_key' => 'test-key',
        'on_demand_type_option' => 'auto_select_lowest_fee',
    ]);
    Location::shouldReceive('orderType')->andReturn(LocationModel::DELIVERY);
    $location = LocationModel::factory()->make();
    Location::shouldReceive('current')->andReturn($location);
    Location::shouldReceive('userPosition')->andReturn(\Igniter\Flame\Geolite\Model\Location::createFromArray([
        'formattedAddress' => '456 Customer St',
    ]));
    Location::shouldReceive('orderDateTime')->andReturn(now());
    $coveredArea = mock(CoveredArea::class, function($mock): void {
        $mock->shouldReceive('deliveryAmount')->andReturn(5.00);
    });
    Location::shouldReceive('coveredArea')->andReturn($coveredArea);
    Cart::shouldReceive('subtotal')->andReturn(25.00);
    Http::fake([
        'https://api.shipday.com/on-demand/services/availability' => Http::response([
            ['name' => 'DoorDash', 'error' => false], // No fee in response
        ]),
    ]);

    $condition = new OnDemandDelivery;
    $result = $condition->beforeApply();

    expect($result)->toBeTrue()
        ->and($condition->getActions())->toBe([['value' => '+5']]);
});

it('handles exception when getting available service fails', function(): void {
    Settings::set([
        'delivery_type' => 'on_demand',
        'api_key' => 'test-key',
    ]);
    Location::shouldReceive('orderType')->andReturn(LocationModel::DELIVERY);
    $location = LocationModel::factory()->make();
    Location::shouldReceive('current')->andReturn($location);
    Location::shouldReceive('userPosition')->andReturn(\Igniter\Flame\Geolite\Model\Location::createFromArray([
        'formattedAddress' => '456 Customer St',
    ]));
    Location::shouldReceive('orderDateTime')->andReturn(now());
    Http::fake([
        'https://api.shipday.com/on-demand/services/availability' => Http::response(['error' => 'Service unavailable'], 500),
    ]);

    $condition = new OnDemandDelivery;
    $result = $condition->beforeApply();

    expect($result)->toBeFalse();
});

it('does not show error alert twice when exception occurs multiple times', function(): void {
    Settings::set([
        'delivery_type' => 'on_demand',
        'api_key' => 'test-key',
    ]);
    Location::shouldReceive('orderType')->andReturn(LocationModel::DELIVERY);
    $location = LocationModel::factory()->make();
    Location::shouldReceive('current')->andReturn($location);
    Location::shouldReceive('userPosition')->andReturn(\Igniter\Flame\Geolite\Model\Location::createFromArray([
        'formattedAddress' => '456 Customer St',
    ]));
    Location::shouldReceive('orderDateTime')->andReturn(now());
    Http::fake([
        'https://api.shipday.com/on-demand/services/availability' => Http::response(['error' => 'Service unavailable'], 500),
    ]);

    $condition1 = new OnDemandDelivery;
    $condition1->beforeApply();

    $condition2 = new OnDemandDelivery;
    $condition2->beforeApply();

    expect($condition2->beforeApply())->toBeFalse();
});

it('returns correct actions with delivery charge', function(): void {
    Settings::set([
        'delivery_type' => 'on_demand',
        'api_key' => 'test-key',
        'on_demand_type_option' => 'auto_select_lowest_fee',
    ]);
    Location::shouldReceive('orderType')->andReturn(LocationModel::DELIVERY);
    $location = LocationModel::factory()->make();
    Location::shouldReceive('current')->andReturn($location);
    Location::shouldReceive('userPosition')->andReturn(\Igniter\Flame\Geolite\Model\Location::createFromArray([
        'formattedAddress' => '456 Customer St',
    ]));
    Location::shouldReceive('orderDateTime')->andReturn(now());
    Http::fake([
        'https://api.shipday.com/on-demand/services/availability' => Http::response([
            ['name' => 'DoorDash', 'fee' => 7.25, 'error' => false],
        ]),
    ]);

    $condition = new OnDemandDelivery;
    $condition->beforeApply();

    expect($condition->getActions())->toBe([['value' => '+7.25']]);
});

it('clears internal cache', function(): void {
    OnDemandDelivery::clearInternalCache();

    expect(OnDemandDelivery::getDeliveryService())->toBeNull();
});
