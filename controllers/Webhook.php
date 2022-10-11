<?php

namespace IgniterLabs\Shipday\Controllers;

use IgniterLabs\DoorDashDrive\Models\Delivery;
use IgniterLabs\DoorDashDrive\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class Webhook extends Controller
{
    public function __invoke(Request $request)
    {
        if (Settings::isConnected() && Settings::validateWebhookToken($request->bearerToken())) {
            if ($this->shouldHandleEvent($request->input('event_name'))) {
                if ($delivery = $this->getDeliveryByUuid($request->input('external_delivery_id'))) {
                    $delivery->fillFromQuote($request->input())->save();

                    if ($delivery->status == 'delivered') {
                        $delivery->markAsDelivered();
                    }

                    if ($delivery->status == 'cancelled') {
                        $delivery->markAsCanceled();
                    }
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    protected function shouldHandleEvent($eventName)
    {
        return in_array($eventName, [
            'DASHER_CONFIRMED',
            'DASHER_CONFIRMED_PICKUP_ARRIVAL',
            'DASHER_PICKED_UP',
            'DASHER_CONFIRMED_DROPOFF_ARRIVAL',
            'DASHER_DROPPED_OFF',
            'DELIVERY_CANCELLED',

            'DELIVERY_RETURN_INITIALIZED',
            'DASHER_CONFIRMED_RETURN_ARRIVAL',
            'DELIVERY_RETURNED',
        ]);
    }

    /**
     * @param string $uuid
     * @return \IgniterLabs\DoorDashDrive\Models\Delivery
     */
    protected function getDeliveryByUuid($uuid)
    {
        return Delivery::whereUuid($uuid)->whereNotNull('order_id')->first();
    }
}
