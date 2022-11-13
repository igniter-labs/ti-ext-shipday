<?php

use Illuminate\Support\Facades\Route;

Route::name('igniterlabs_shipday_webhook')
    ->post('shipday/webhook/{token}', \IgniterLabs\Shipday\Controllers\Webhook::class);
