<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Tests\Actions;

use Igniter\Flame\Exception\SystemException;
use Igniter\User\Models\User;
use Illuminate\Support\Facades\Http;

it('returns shipday driver details when driver exists', function(): void {
    $carrier = User::factory()->create([
        'shipday_id' => 'driver123',
    ]);
    Http::fake([
        'https://api.shipday.com/carriers' => Http::response([['id' => 'abc1234', 'email' => $carrier->email]]),
    ]);

    $response = $carrier->createOrGetShipdayDriver();

    expect($response)->toBeArray()
        ->and($response['id'])->toEqual('abc1234')
        ->and($response['email'])->toEqual($carrier->email);
});

it('creates shipday driver successfully when conditions are met', function(): void {
    $carrier = User::factory()->create([
        'name' => 'Driver Name',
        'telephone' => '1234567890',
    ]);
    Http::fake([
        'https://api.shipday.com/carriers' => Http::sequence()
            ->push([])
            ->push([])
            ->push([
                ['id' => 'driver123', 'email' => $carrier->email],
            ]),
    ]);

    $response = $carrier->createAsShipdayDriver();

    expect($response)->toBeArray()
        ->and($response['id'])->toEqual('driver123')
        ->and($response['email'])->toEqual($carrier->email);
});

it('throws exception when creating shipday driver for existing driver', function(): void {
    $carrier = User::factory()->create([
        'name' => 'Driver Name',
        'shipday_id' => 'driver123',
    ]);

    expect(fn() => $carrier->createAsShipdayDriver())->toThrow(SystemException::class,
        'Driver Name is already a Shipday driver with ID driver123.',
    );
});

it('throws exception when creating shipday driver without telephone', function(): void {
    $carrier = User::factory()->create([
        'name' => 'Driver Name',
    ]);

    expect(fn() => $carrier->createAsShipdayDriver())->toThrow(SystemException::class,
        'Staff Driver Name must have a telephone number to create a Shipday driver.',
    );
});

it('throws exception when asserting shipday driver for non-existing driver', function(): void {
    $carrier = User::factory()->create([
        'name' => 'Driver Name',
    ]);
    expect(fn() => $carrier->assertShipdayDriver())->toThrow(SystemException::class,
        'Staff Driver Name is not a Shipday driver yet. See the createAsShipdayDriver method.',
    );
});
