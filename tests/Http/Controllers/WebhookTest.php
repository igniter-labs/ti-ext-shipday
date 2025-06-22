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
                'shipday_status' => 'ORDER_COMPLETED',
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
            'order' => ['id' => '12345'],
        ])
        ->assertStatus(200);

    expect($order->fresh()->status_id)->toBe(2); // Check if the order status was updated
});
