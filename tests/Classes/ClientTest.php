<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Tests\Classes;

use IgniterLabs\Shipday\Classes\Client;
use IgniterLabs\Shipday\Exceptions\ClientException;
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
