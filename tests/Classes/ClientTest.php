<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Tests\Classes;

use IgniterLabs\Shipday\Classes\Client;
use IgniterLabs\Shipday\Exceptions\ClientException;
use IgniterLabs\Shipday\Models\Settings;
use Illuminate\Support\Facades\Http;

it('deletes order successfully', function(): void {
    Http::fake([
        'https://api.shipday.com/orders/abc1234' => Http::response(['orderId' => 'new-order-id']),
    ]);

    $response = resolve(Client::class)->deleteOrder('abc1234');

    expect($response)->toBe(['orderId' => 'new-order-id']);
});

it('throws exception when deleting order fails', function(): void {
    Http::fake([
        'https://api.shipday.com/orders/abc1234' => Http::response(['error' => 'Invalid parameters'], 400),
    ]);

    expect(fn() => resolve(Client::class)->deleteOrder('abc1234'))->toThrow(ClientException::class);
});

it('assigns on-demand delivery service successfully', function(): void {
    Http::fake([
        'https://api.shipday.com/on-demand/assign' => Http::response(['id' => 'ondemand123', 'status' => 'assigned']),
    ]);

    $response = resolve(Client::class)->assignOnDemandDeliveryService([
        'name' => 'DoorDash',
        'orderId' => '12345',
        'tip' => 5.00,
    ]);

    expect($response)->toBe(['id' => 'ondemand123', 'status' => 'assigned']);
});

it('gets on-demand services successfully', function(): void {
    Settings::set(['api_key' => 'test-key']);
    Http::fake([
        'https://api.shipday.com/on-demand/services' => Http::response([
            ['name' => 'DoorDash'],
            ['name' => 'Postmates'],
        ]),
    ]);

    $services = resolve(Client::class)->getOnDemandServices();

    expect($services)->toBeArray()
        ->and($services)->toContain('DoorDash');
});

it('returns empty array when api key is not set', function(): void {
    Settings::set(['api_key' => null]);

    $services = resolve(Client::class)->getOnDemandServices();

    expect($services)->toBe([]);
});

it('returns empty array when exception occurs fetching services', function(): void {
    Settings::set(['api_key' => 'test-key']);
    Http::fake([
        'https://api.shipday.com/on-demand/services' => Http::response(['error' => 'Unauthorized'], 401),
    ]);

    $services = resolve(Client::class)->getOnDemandServices();

    expect($services)->toBe([]);
});

it('gets available service with manual selection', function(): void {
    Settings::set([
        'api_key' => 'test-key',
        'on_demand_type_option' => 'manual_selection',
        'delivery_service' => 'DoorDash',
    ]);
    Http::fake([
        'https://api.shipday.com/on-demand/services/availability' => Http::response([
            ['name' => 'DoorDash', 'fee' => 3.50, 'error' => false],
            ['name' => 'Postmates', 'fee' => 4.00, 'error' => false],
        ]),
    ]);

    $service = resolve(Client::class)->getAvailableService([
        'pickup_address' => '123 Restaurant St',
        'delivery_address' => '456 Customer St',
        'pickup_time' => '2024-01-15T18:00:00Z',
    ]);

    expect($service)->toBeArray()
        ->and($service['name'])->toBe('DoorDash')
        ->and($service['fee'])->toBe(3.50);
});

it('gets available service with auto select lowest fee', function(): void {
    Settings::set([
        'api_key' => 'test-key',
        'on_demand_type_option' => 'auto_select_lowest_fee',
    ]);
    Http::fake([
        'https://api.shipday.com/on-demand/services/availability' => Http::response([
            ['name' => 'DoorDash', 'fee' => 4.00, 'error' => false],
            ['name' => 'Postmates', 'fee' => 3.50, 'error' => true],
            ['name' => 'Uber', 'fee' => 5.00, 'error' => false],
        ]),
    ]);

    $service = resolve(Client::class)->getAvailableService([
        'pickup_address' => '123 Restaurant St',
        'delivery_address' => '456 Customer St',
        'pickup_time' => '2024-01-15T18:00:00Z',
    ]);

    expect($service)->toBeArray()
        ->and($service['name'])->toBe('DoorDash')
        ->and($service['fee'])->toBe(4);
});

it('gets available service with auto select highest fee', function(): void {
    Settings::set([
        'api_key' => 'test-key',
        'on_demand_type_option' => 'auto_select_highest_fee',
    ]);
    Http::fake([
        'https://api.shipday.com/on-demand/services/availability' => Http::response([
            ['name' => 'DoorDash', 'fee' => 4.00, 'error' => false],
            ['name' => 'Postmates', 'fee' => 3.50, 'error' => false],
            ['name' => 'Uber', 'fee' => 5.00, 'error' => true],
        ]),
    ]);

    $service = resolve(Client::class)->getAvailableService([
        'pickup_address' => '123 Restaurant St',
        'delivery_address' => '456 Customer St',
        'pickup_time' => '2024-01-15T18:00:00Z',
    ]);

    expect($service)->toBeArray()
        ->and($service['name'])->toBe('DoorDash')
        ->and($service['fee'])->toBe(4);
});

it('filters out services with errors when selecting by name', function(): void {
    Settings::set([
        'api_key' => 'test-key',
        'on_demand_type_option' => 'manual_selection',
        'delivery_service' => 'DoorDash',
    ]);
    Http::fake([
        'https://api.shipday.com/on-demand/services/availability' => Http::response([
            ['name' => 'DoorDash', 'fee' => 3.50, 'error' => true],
            ['name' => 'Postmates', 'fee' => 4.00, 'error' => false],
        ]),
    ]);

    $service = resolve(Client::class)->getAvailableService([
        'pickup_address' => '123 Restaurant St',
        'delivery_address' => '456 Customer St',
        'pickup_time' => '2024-01-15T18:00:00Z',
    ]);

    expect($service)->toBeNull();
});

it('returns null when no service matches manual selection', function(): void {
    Settings::set([
        'api_key' => 'test-key',
        'on_demand_type_option' => 'manual_selection',
        'delivery_service' => 'Uber',
    ]);
    Http::fake([
        'https://api.shipday.com/on-demand/services/availability' => Http::response([
            ['name' => 'DoorDash', 'fee' => 3.50, 'error' => false],
            ['name' => 'Postmates', 'fee' => 4.00, 'error' => false],
        ]),
    ]);

    $service = resolve(Client::class)->getAvailableService([
        'pickup_address' => '123 Restaurant St',
        'delivery_address' => '456 Customer St',
        'pickup_time' => '2024-01-15T18:00:00Z',
    ]);

    expect($service)->toBeNull();
});

it('cancels on-demand delivery successfully', function(): void {
    Http::fake([
        'https://api.shipday.com/on-demand/cancel/12345' => Http::response(['success' => true]),
    ]);

    $response = resolve(Client::class)->cancelOnDemandDelivery('12345');

    expect($response)->toBe(['success' => true]);
});
