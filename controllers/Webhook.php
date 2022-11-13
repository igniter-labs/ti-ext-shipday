<?php

namespace IgniterLabs\Shipday\Controllers;

use Admin\Models\Orders_model;
use IgniterLabs\Shipday\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class Webhook extends Controller
{
    public function __invoke(Request $request, $token)
    {
        if (Settings::isConnected() && Settings::validateWebhookToken($token)) {
            if ($this->shouldHandleEvent($request->input('event'))) {
                if ($order = $this->getOrderByOrderId($request->input('order.order_number'))) {
                    $log = $order->logShipdayDelivery($request->input());

                    $statusId = Settings::getShipdayStatusMap()->get($log->status);
                    if ($statusId && $order->status_id != $statusId) {
                        $order->updateOrderStatus($statusId, ['notify' => false]);
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
    protected function getOrderByOrderId($orderId)
    {
        return Orders_model::find($orderId);
    }
}
