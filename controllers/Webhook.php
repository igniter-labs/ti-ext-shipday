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
            if ($this->shouldHandleEvent($eventName = $request->input('event'))) {
                $order = $this->getOrderByShipdayOrderId($request->input('order.id'));
                if ($order && $order->isDeliveryType() && Settings::isConnected()) {
                    $log = $order->logShipdayDelivery($request->input());

                    $eventName = $eventName !== 'ORDER_COMPLETED' ? $log->status : $eventName;
                    $statusId = Settings::getShipdayStatusMap()->get($eventName);
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
     * @return \Admin\Models\Orders_model|null
     */
    protected function getOrderByShipdayOrderId($orderId)
    {
        return Orders_model::firstWhere('shipday_id', $orderId);
    }
}
