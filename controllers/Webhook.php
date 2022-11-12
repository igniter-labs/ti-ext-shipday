<?php

namespace IgniterLabs\Shipday\Controllers;

use IgniterLabs\Shipday\Models\Delivery;
use IgniterLabs\Shipday\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class Webhook extends Controller
{
    public function __invoke(Request $request)
    {
        if (Settings::isConnected() && Settings::validateWebhookToken($request->bearerToken())) {
            if ($this->shouldHandleEvent($request->input('event'))) {
                if ($delivery = $this->getActiveDeliveryByOrderId($request->input('order.order_number'))) {
                    $delivery->fillFromRemote($request->input())->save();

                    if ($delivery->order && ($statusId = Settings::getShipdayStatusMap()->get($delivery->status))) {
                        $delivery->order->updateOrderStatus($statusId, ['notify' => false]);
                    }
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    protected function shouldHandleEvent($eventName)
    {
        return in_array($eventName, [
            'ORDER_ASSIGNED',
            'ORDER_ACCEPTED_AND_STARTED',
            'ORDER_ONTHEWAY',
            'ORDER_COMPLETED',
            'ORDER_FAILED',
        ]);
    }

    /**
     * @param string $shipdayId
     * @return \IgniterLabs\DoorDashDrive\Models\Delivery
     */
    protected function getActiveDeliveryByOrderId($orderId)
    {
        return Delivery::where('order_id', $orderId)
            ->orderBy('shipday_id', 'desc')
            ->first();
    }
}
