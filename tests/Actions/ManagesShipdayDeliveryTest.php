<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Tests\Actions;

use Igniter\Cart\Models\Menu;
use Igniter\Cart\Models\Order;
use Igniter\Flame\Exception\SystemException;
use Igniter\Local\Models\Location;
use Igniter\User\Models\Address;
use IgniterLabs\Shipday\Models\DeliveryLog;
use Illuminate\Support\Facades\Http;

it('adds shipday id and tracking url to mail data', function(): void {
    Order::flushEventListeners();
    $order = Order::factory()->create([
        'shipday_id' => '12345',
    ]);
    Http::fake([
        'https://api.shipday.com/orders/'.$order->getKey() => Http::response([
            [
                'orderId' => '12345',
                'trackingLink' => 'https://tracking.shipday.com/12345',
            ],
        ]),
    ]);

    $mailData = $order->mailGetData();

    expect($mailData)->toBeArray()
        ->and($mailData['shipday_id'])->toEqual('12345')
        ->and($mailData['shipday_tracking_url'])->toBe('https://tracking.shipday.com/12345');
});

it('throws exception when asserting Shipday delivery fails', function(): void {
    $order = Order::factory()->create();

    expect(fn() => $order->assertShipdayDelivery())->toThrow(SystemException::class);
});

it('throws exception when creating delivery already exists', function(): void {
    $order = Order::factory()->create([
        'shipday_id' => '12345',
    ]);

    expect(fn() => $order->createAsShipdayDelivery())->toThrow(SystemException::class,
        sprintf('Order ID %s is already a Shipday delivery with ID 12345.', $order->getKey()),
    );
});

it('throws exception when creating Shipday delivery for non-delivery order', function(): void {
    $order = Order::factory()->create([
        'order_type' => 'dine-in',
    ]);

    expect(fn() => $order->createAsShipdayDelivery())->toThrow(SystemException::class,
        sprintf('Order ID %s must be a delivery order.', $order->getKey()),
    );
});

it('edits shipday delivery order successfully', function(): void {
    Http::fake([
        'https://api.shipday.com/order/edit/12345' => Http::response(['orderId' => '12345', 'success' => true]),
    ]);
    $address = Address::factory()->create();
    $location = Location::factory()->create();
    $order = Order::factory()
        ->for($address, 'address')
        ->for($location, 'location')
        ->create([
            'shipday_id' => '12345',
            'order_type' => 'delivery',
        ]);

    $params = ['note' => 'Updated note'];

    $response = $order->editShipdayDeliveryOrder($params);

    expect($response)->toBeArray()
        ->and($response['orderId'])->toBe('12345')
        ->and($response['success'])->toBeTrue();
});

it('updates Shipday delivery status successfully', function(): void {
    $order = Order::factory()->create([
        'shipday_id' => '12345',
    ]);
    Http::fake([
        'https://api.shipday.com/orders/12345/status' => Http::response([
            'orderId' => 'abc1234',
            'order_status' => 'DELIVERED',
            'success' => true,
        ]),
        'https://api.shipday.com/orders/'.$order->getKey() => Http::response(['orderId' => 'abc1234']),
    ]);

    $response = $order->updateShipdayDeliveryStatus('DELIVERED');

    expect($response)->toBeInstanceOf(DeliveryLog::class)
        ->and($response->status)->toBe('DELIVERED');
});

it('throws exception when updating Shipday delivery status fails', function(): void {
    $order = Order::factory()->create([
        'shipday_id' => '12345',
    ]);
    Http::fake([
        'https://api.shipday.com/orders/*/status' => Http::response(['success' => false]),
        'https://api.shipday.com/orders/'.$order->getKey() => Http::response(['orderId' => 'abc1234']),
    ]);

    expect(fn() => $order->updateShipdayDeliveryStatus('DELIVERED'))->toThrow(SystemException::class,
        'Unable to update Shipday delivery status to DELIVERED.',
    );
});

it('assigns order to on-demand delivery service successfully', function(): void {
    $address = Address::factory()->create();
    $location = Location::factory()->create();
    $order = Order::factory()
        ->for($address, 'address')
        ->for($location, 'location')
        ->create([
            'shipday_id' => '12345',
            'order_type' => 'delivery',
        ]);
    $order->addOrderTotals([
        ['code' => 'tip', 'value' => 5.00],
    ]);
    Http::fake([
        'https://api.shipday.com/on-demand/services/availability' => Http::response([
            ['name' => 'DoorDash', 'fee' => 3.50, 'error' => false],
        ]),
        'https://api.shipday.com/on-demand/assign' => Http::response([
            'id' => 'ondemand123',
            'status' => 'assigned',
            'trackingUrl' => 'https://tracking.shipday.com/ondemand123',
        ]),
    ]);

    $result = $order->assignOrderToShipdayOnDemandDeliveryService();

    expect($result)->toBeNull(); // rescue returns null on success
});

it('handles exception when no delivery service available', function(): void {
    $address = Address::factory()->create();
    $location = Location::factory()->create();
    $order = Order::factory()
        ->for($address, 'address')
        ->for($location, 'location')
        ->create([
            'shipday_id' => '12345',
            'order_type' => 'delivery',
        ]);
    Http::fake([
        'https://api.shipday.com/on-demand/services/availability' => Http::response([]),
    ]);

    $result = $order->assignOrderToShipdayOnDemandDeliveryService();

    expect($result)->toBeNull(); // rescue catches exception and returns null
});

it('cancels on-demand delivery successfully', function(): void {
    $order = Order::factory()->create([
        'shipday_id' => '12345',
    ]);
    Http::fake([
        'https://api.shipday.com/on-demand/cancel/12345' => Http::response(['success' => true]),
    ]);

    $order->cancelShipdayOnDemandDelivery();

    Http::assertSent(fn($request): bool => $request->url() === 'https://api.shipday.com/on-demand/cancel/12345');
});

it('throws exception when canceling on-demand delivery for order without shipday id', function(): void {
    $order = Order::factory()->create();

    expect(fn() => $order->cancelShipdayOnDemandDelivery())->toThrow(SystemException::class);
});

it('sends item comment as detail and unit price including options when creating delivery', function(): void {
    $address = Address::factory()->create();
    $location = Location::factory()->create();
    $order = Order::factory()
        ->for($address, 'address')
        ->for($location, 'location')
        ->create([
            'order_type' => 'delivery',
        ]);
    $order->menus()->create([
        'menu_id' => Menu::factory()->create()->getKey(),
        'name' => 'Burger',
        'quantity' => 2,
        'price' => 10,
        'subtotal' => 25,
        'comment' => 'No onions',
    ]);
    Http::fake([
        'https://api.shipday.com/orders' => Http::response(['orderId' => 'abc1234']),
        'https://api.shipday.com/orders/'.$order->getKey() => Http::response([['orderId' => 'abc1234']]),
    ]);

    $order->createAsShipdayDelivery();

    Http::assertSent(function($request): bool {
        if ($request->url() !== 'https://api.shipday.com/orders' || $request->method() !== 'POST') {
            return false;
        }

        $item = $request->data()['orderItem'][0] ?? [];

        return $item['name'] === 'Burger'
            && $item['quantity'] === 2
            && (float)$item['unitPrice'] === 12.5
            && $item['detail'] === 'No onions';
    });
});
