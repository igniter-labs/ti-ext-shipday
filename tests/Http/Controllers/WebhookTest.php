<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Tests\Http\Controllers;

use Igniter\Cart\Models\Order;
use IgniterLabs\Shipday\Models\Settings;
use Illuminate\Support\Facades\Http;

it('handles valid webhook event and updates order status', function(): void {
    Settings::flushEventListeners();
    Settings::set([
        'api_key' => str_random(),
        'webhook_token' => $token = str_random(),
        'status_map' => [
            [
                'order_status' => 2,
                'shipday_status' => 'ALREADY_DELIVERED',
            ],
        ],
    ]);
    $order = Order::factory()->create([
        'shipday_id' => '12345',
        'status_id' => 1,
    ]);
    Http::fake([
        'https://api.shipday.com/orders/'.$order->getKey() => Http::response([
            [
                'orderId' => '12345',
                'orderStatusAdmin' => 'PENDING',
            ],
        ]),
    ]);

    $this
        ->postJson(route('igniterlabs_shipday_webhook', ['token' => $token]), [
            'event' => 'ORDER_COMPLETED',
            'order_status' => 'ALREADY_DELIVERED',
            'order' => ['id' => '12345'],
        ])
        ->assertStatus(200);

    expect($order->fresh()->status_id)->toBe(2); // Check if the order status was updated
});

it('handles webhook event with numeric order id', function(): void {
    Settings::flushEventListeners();
    Settings::set([
        'api_key' => str_random(),
        'webhook_token' => $token = str_random(),
        'status_map' => [
            ['order_status' => 3, 'shipday_status' => 'PICKED_UP'],
        ],
    ]);
    $order = Order::factory()->create([
        'shipday_id' => 123456,
        'status_id' => 1,
    ]);
    Http::fake([
        'https://api.shipday.com/orders/'.$order->getKey() => Http::response([
            ['orderId' => 123456],
        ]),
    ]);

    $this
        ->postJson(route('igniterlabs_shipday_webhook', ['token' => $token]), [
            'event' => 'ORDER_PIKEDUP',
            'order_status' => 'PICKED_UP',
            'order' => ['id' => 123456],
        ])
        ->assertStatus(200)
        ->assertJson(['status' => 'ok']);

    expect($order->fresh()->status_id)->toBe(3)
        ->and($order->shipday_logs()->count())->toBe(1);
});

it('maps legacy ORDER_COMPLETED status map to delivered status', function(): void {
    Settings::flushEventListeners();
    Settings::set([
        'api_key' => str_random(),
        'webhook_token' => $token = str_random(),
        'status_map' => [
            ['order_status' => 5, 'shipday_status' => 'ORDER_COMPLETED'],
        ],
    ]);
    $order = Order::factory()->create([
        'shipday_id' => '12345',
        'status_id' => 1,
    ]);
    Http::fake([
        'https://api.shipday.com/orders/'.$order->getKey() => Http::response([
            ['orderId' => '12345'],
        ]),
    ]);

    $this
        ->postJson(route('igniterlabs_shipday_webhook', ['token' => $token]), [
            'event' => 'ORDER_COMPLETED',
            'order_status' => 'ALREADY_DELIVERED',
            'order' => ['id' => 12345],
        ])
        ->assertStatus(200);

    expect($order->fresh()->status_id)->toBe(5);
});

it('ignores webhook event without a usable order id', function(?array $order): void {
    Settings::flushEventListeners();
    Settings::set([
        'api_key' => str_random(),
        'webhook_token' => $token = str_random(),
    ]);
    Http::fake();

    $payload = ['event' => 'ORDER_COMPLETED', 'order_status' => 'ALREADY_DELIVERED'];
    if (!is_null($order)) {
        $payload['order'] = $order;
    }

    $this
        ->postJson(route('igniterlabs_shipday_webhook', ['token' => $token]), $payload)
        ->assertStatus(200)
        ->assertJson(['status' => 'ok']);

    Http::assertNothingSent();
})->with([
    'missing order' => [null],
    'null id' => [['id' => null]],
    'empty id' => [['id' => '']],
    'array id' => [['id' => [1]]],
]);
