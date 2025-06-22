<?php

use IgniterLabs\Shipday\Http\Controllers\Webhook;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'shipday',
    'middleware' => ['web'],
], function(): void {
    Route::name('igniterlabs_shipday_webhook')->post('webhook/{token}', Webhook::class);
});
