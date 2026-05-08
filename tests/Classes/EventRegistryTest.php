<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Tests\Classes;

use Exception;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Widgets\Form;
use Igniter\Cart\Http\Controllers\Orders;
use Igniter\Cart\Http\Requests\DeliverySettingsRequest;
use Igniter\Cart\Models\Order;
use Igniter\Local\Models\Location;
use Igniter\Local\Models\LocationSettings;
use Igniter\User\Models\Address;
use Igniter\User\Models\AssignableLog;
use Igniter\User\Models\User;
use Igniter\User\Models\UserGroup;
use IgniterLabs\Shipday\CartConditions\OnDemandDelivery;
use IgniterLabs\Shipday\Models\Settings;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

beforeEach(function(): void {
    Settings::set([
        'api_key' => str_random(),
        'webhook_token' => str_random(),
    ]);
});

it('adds shipday attempts tab to order details page when order type is delivery', function(): void {
    $order = Order::factory()->create([
        'order_type' => 'delivery',
    ]);
    $formWidget = new Form(resolve(Orders::class), [
        'model' => $order,
    ]);
    $formWidget->bindToController();

    expect($formWidget->getFields())->toHaveKey('shipday_logs');
});

it('does not add shipday attempts tab to order details page when order type is not delivery', function(): void {
    $order = Order::factory()->create([
        'order_type' => 'collection',
    ]);
    $formWidget = new Form(resolve(Orders::class), [
        'model' => $order,
    ]);
    $formWidget->bindToController();

    expect($formWidget->getFields())->not->toHaveKey('shipday_logs');
});

it('extends location delivery settings fields with shipday wait time', function(): void {
    $formWidget = new Form(resolve(Orders::class), [
        'model' => $model = new LocationSettings(['item' => 'delivery']),
    ]);

    Event::dispatch('admin.form.extendFieldsBefore', [$formWidget, $model]);

    expect($formWidget->fields)->toHaveKey('shipday_wait_time')
        ->and($formWidget->fields['shipday_wait_time']['default'])->toBe(15);
});

it('extends delivery settings request validator with shipday wait time rules', function(): void {
    $formRequest = new DeliverySettingsRequest;
    $dataHolder = (object)['rules' => [], 'attributes' => []];

    Event::dispatch('system.formRequest.extendValidator', [$formRequest, $dataHolder]);

    expect($dataHolder->rules)->toHaveKey('shipday_wait_time')
        ->and($dataHolder->rules['shipday_wait_time'])->toContain('integer', 'min:0')
        ->and($dataHolder->attributes)->toHaveKey('shipday_wait_time');
});

it('marks shipday order as ready for delivery when status matches', function(): void {
    Http::fake([
        'https://api.shipday.com/orders/*' => Http::response(['orderStatusAdmin' => 'PENDING']),
    ]);
    $status = Status::factory()->create();
    Settings::set([
        'ready_for_pickup_status_id' => $status->getKey(),
    ]);
    $order = Order::factory()->create([
        'order_type' => 'delivery',
        'shipday_id' => 1234,
    ]);

    $order->updateOrderStatus($status->getKey());

    expect($order->status_id)->toBe($status->getKey());
});

it('assigns driver to shipday order when conditions are met', function(): void {
    $order = Order::factory()->create([
        'order_type' => 'delivery',
        'shipday_id' => 1234,
    ]);
    $assignableLog = AssignableLog::create(['assignable_id' => $order->getKey(), 'assignable_type' => 'orders']);
    $group = UserGroup::factory();
    $assignableLog->assignee = User::factory()->has($group, 'groups')->create(['telephone' => '1234567890']);
    $user = $assignableLog->assignee;
    $assignableLog->assignee_group_id = $user->groups->first()->getKey();
    Settings::set([
        'delivery_staff_group' => $assignableLog->assignee_group_id,
    ]);
    Http::fake([
        'https://api.shipday.com/carriers' => Http::response([['id' => 'abc1234', 'email' => $user->email]]),
        'https://api.shipday.com/orders/assign/*' => Http::response([]),
        'https://api.shipday.com/orders/'.$order->getKey() => Http::response([
            [
                'orderId' => '12345',
                'trackingLink' => 'https://tracking.shipday.com/12345',
            ],
        ]),
    ]);

    Event::dispatch('admin.assignable.assigned', [$order, $assignableLog]);

    expect($assignableLog->assignee->shipday_id)->toBe('abc1234');
});

it('creates shipday order on payment processed when conditions are met', function(): void {
    $address = Address::factory()->create();
    $location = Location::factory()->create();
    $order = Order::factory()
        ->for($address, 'address')
        ->for($location, 'location')
        ->create([
            'order_type' => 'delivery',
        ]);
    $order->addOrderMenus([
        (object)[
            'id' => 1,
            'name' => 'Test Menu Item',
            'qty' => 1,
            'price' => 10.00,
            'subtotal' => 10.00,
            'comment' => 'Extra, cheese',
            'options' => [
                (object)[
                    'id' => 1,
                    'values' => [
                        (object)['id' => 1, 'name' => 'Spicy', 'price' => 0.50, 'qty' => 1],
                    ],
                ],
            ],
        ],
    ]);
    Http::fake([
        'https://api.shipday.com/orders' => Http::response(['orderId' => 'abc1234']),
        'https://api.shipday.com/orders/'.$order->getKey() => Http::response(['orderId' => 'abc1234']),
    ]);

    Event::dispatch('admin.order.paymentProcessed', [$order]);

    expect($order->shipday_id)->toBe('abc1234');
});

it('validates on-demand delivery service on checkout when service is not available', function(): void {
    Settings::set([
        'delivery_type' => 'on_demand',
        'api_key' => 'test-key',
    ]);
    $order = Order::factory()->create([
        'order_type' => 'delivery',
    ]);
    OnDemandDelivery::clearInternalCache();

    expect(fn() => Event::dispatch('igniter.checkout.afterSaveOrder', [$order]))
        ->toThrow(Exception::class);
});

it('does not validate when on-demand delivery is not supported', function(): void {
    Settings::set([
        'delivery_type' => 'in_house',
        'api_key' => 'test-key',
    ]);
    $order = Order::factory()->create([
        'order_type' => 'delivery',
    ]);

    Event::dispatch('igniter.checkout.afterSaveOrder', [$order]);

    expect(true)->toBeTrue(); // Should not throw exception
});

it('assigns on-demand delivery service when order is marked ready for pickup', function(): void {
    Settings::set([
        'delivery_type' => 'on_demand',
        'api_key' => 'test-key',
        'ready_for_pickup_status_id' => 5,
        'on_demand_type_option' => 'auto_select_lowest_fee',
    ]);
    $address = Address::factory()->create();
    $location = Location::factory()->create();
    $order = Order::factory()
        ->for($address, 'address')
        ->for($location, 'location')
        ->create([
            'order_type' => 'delivery',
            'shipday_id' => 1234,
        ]);
    $order->addOrderTotals([
        ['code' => 'tip', 'value' => 5.00],
    ]);
    Http::fake([
        'https://api.shipday.com/orders/'.$order->getKey() => Http::response(['orderStatusAdmin' => 'PENDING']),
        'https://api.shipday.com/orders/*/meta' => Http::response(['success' => true]),
        'https://api.shipday.com/on-demand/services/availability' => Http::response([
            ['name' => 'DoorDash', 'fee' => 3.50, 'error' => false],
        ]),
        'https://api.shipday.com/on-demand/assign' => Http::response([
            'id' => 'ondemand123',
            'status' => 'assigned',
        ]),
    ]);

    $order->updateOrderStatus(5);

    Http::assertSent(fn($request): bool => str_contains((string) $request->url(), 'on-demand/assign'));
});

it('cancels on-demand delivery when order is canceled', function(): void {
    Settings::set([
        'delivery_type' => 'on_demand',
        'api_key' => 'test-key',
    ]);
    $status = Status::factory()->create();
    setting()->set(['canceled_order_status' => $status->getKey()]);
    $order = Order::factory()->create([
        'order_type' => 'delivery',
        'shipday_id' => 1234,
    ]);
    Http::fake([
        'https://api.shipday.com/orders/'.$order->getKey() => Http::response(['orderStatusAdmin' => 'PENDING']),
        'https://api.shipday.com/on-demand/cancel/1234' => Http::response(['success' => true]),
    ]);

    $order->updateOrderStatus($status->getKey());

    Http::assertSent(fn($request): bool => str_contains((string) $request->url(), 'on-demand/cancel'));
});

it('does not cancel on-demand delivery when order is not canceled status', function(): void {
    Settings::set([
        'delivery_type' => 'on_demand',
        'api_key' => 'test-key',
    ]);
    $status = Status::factory()->create();
    $otherStatus = Status::factory()->create();
    setting()->set(['canceled_order_status' => $status->getKey()]);
    $order = Order::factory()->create([
        'order_type' => 'delivery',
        'shipday_id' => 1234,
    ]);
    Http::fake([
        'https://api.shipday.com/orders/'.$order->getKey() => Http::response(['orderStatusAdmin' => 'PENDING']),
    ]);

    $order->updateOrderStatus($otherStatus->getKey()); // Different status

    Http::assertNotSent(fn($request): bool => str_contains((string) $request->url(), 'on-demand/cancel'));
});
