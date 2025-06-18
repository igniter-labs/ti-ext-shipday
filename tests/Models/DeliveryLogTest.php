<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Tests\Models;

use Igniter\Cart\Models\Order;
use Igniter\User\Models\User;
use IgniterLabs\Shipday\Models\DeliveryLog;

it('logs delivery update with valid response and request data', function(): void {
    $carrier = User::factory()->create([
        'shipday_id' => 'carrier123',
    ]);
    $order = Order::factory()->create([
        'shipday_id' => '12345',
    ]);

    $response = [
        'orderId' => '67890',
        'orderStatus' => ['orderState' => 'DELIVERED'],
        'shipday_order_details' => ['trackingLink' => 'http://tracking.url'],
        'assignedCarrier' => ['id' => 'carrier123'],
    ];
    $request = ['deliveryFee' => 15.50];

    $log = DeliveryLog::logUpdate($order, $response, $request);

    expect($log->order_id)->toBe($order->getKey())
        ->and($log->shipday_id)->toBe('67890')
        ->and($log->fee)->toBe(15.50)
        ->and($log->status)->toBe('DELIVERED')
        ->and($log->tracking_url)->toBe('http://tracking.url')
        ->and($log->carrier_id)->toBe('carrier123')
        ->and($log->carrier_name)->toBe($carrier->full_name)
        ->and($log->created_since)->not->toBeNull();
});

it('logs delivery update with default status when response lacks order state', function(): void {
    $order = Order::factory()->create([
        'shipday_id' => '12345',
    ]);

    $response = ['orderId' => '67890'];
    $request = ['deliveryFee' => 15.50];

    $log = DeliveryLog::logUpdate($order, $response, $request);

    expect($log->status)->toBe('SENT');
});

it('updates model shipday_id to null when delivery is cancelled', function(): void {
    $order = Order::factory()->create([
        'shipday_id' => '12345',
    ]);

    $response = [
        'orderId' => '67890',
        'orderStatus' => ['orderState' => 'FAILED_DELIVERY'],
    ];
    $request = [];

    DeliveryLog::logUpdate($order, $response, $request);

    expect($order->shipday_id)->toBeNull();
});

it('checks when delivery status is cancelled', function(): void {
    $canceledLog = new DeliveryLog;
    $canceledLog->status = 'FAILED_DELIVERY';

    $successLog = new DeliveryLog;
    $successLog->status = 'DELIVERED';

    expect($canceledLog->isCancelled())->toBeTrue()
        ->and($successLog->isCancelled())->toBeFalse();
});
