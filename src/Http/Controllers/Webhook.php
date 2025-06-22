<?php

declare(strict_types=1);

namespace IgniterLabs\Shipday\Http\Controllers;

use Igniter\Cart\Models\Order;
use IgniterLabs\Shipday\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class Webhook extends Controller
{
    public function __invoke(Request $request, $token)
    {
        if ($this->shouldHandleEvent($token, $eventName = $request->input('event'))) {
            /** @var Order|null $order */
            $order = $this->getOrderByShipdayOrderId($request->input('order.id'));
            if ($order && $order->isDeliveryType()) {
                $log = $order->logShipdayDelivery($request->input());

                $eventName = $eventName !== 'ORDER_COMPLETED' ? $log->status : $eventName;
                $statusId = Settings::getShipdayStatusMap()->get($eventName);
                if ($statusId && $order->status_id != $statusId) {
                    $order->updateOrderStatus($statusId, ['notify' => false]);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    protected function shouldHandleEvent($token, $eventName): bool
    {
        return Settings::isConnected() && Settings::validateWebhookToken($token) && in_array($eventName, [
            'ORDER_ASSIGNED',
            'ORDER_ACCEPTED_AND_STARTED',
            'ORDER_ONTHEWAY',
            'ORDER_COMPLETED',
            'ORDER_FAILED',
            'ORDER_INCOMPLETE',
            'ORDER_DELETE',
            'ORDER_INSERTED',
            'ORDER_PIKEDUP',
            'ORDER_UNASSIGNED',
            'ORDER_PIKEDUP_REMOVED',
            'ORDER_ONTHEWAY_REMOVED',
            'ORDER_POD_UPLOAD',
        ]);
    }

    protected function getOrderByShipdayOrderId(string $orderId): ?Order
    {
        return Order::query()->firstWhere('shipday_id', $orderId);
    }
}
