<?php

namespace IgniterLabs\Shipday\Classes;

use Admin\Models\Locations_model;
use Admin\Models\Orders_model;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Geolite\Facades\Geocoder;
use IgniterLabs\Shipday\Models\Settings;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class Manager
{
    public $client;

    public function __construct()
    {
        $this->client = new Client(Settings::getApiKey());
    }

    public function createOrder(Orders_model $order)
    {
        if ($order->order_type !== Locations_model::DELIVERY)
            return;

        $delivery = Delivery::fetchDelivery($order->order_id);

        if (is_null($delivery)) {
            $delivery = $this->createDelivery($order);
            $payload = $this->prepareDeliveryRequest($delivery, $order);
            $request = $this->sendRequest('POST', 'orders', $payload);
            $response = json_decode($request->getBody()->getContents(), true);
            $delivery->insert_response = $response;
            $delivery->shipday_order_id = array_get($response, 'orderId');
            $delivery->save();
        }

        if ($delivery->shipday_order_id && is_null($delivery->estimate_order_id)) {
            $response = $this->estimateDeliveryAmount($delivery);
            $oldFee = $delivery->fee;
            Delivery::updateDelivery($response, $delivery);

            if (isset($response[0]['error']) && $response[0]['error'])
                return;

            session()->put('shipday_delivery_id', $delivery->id);

            if ($oldFee !== $response[0]['fee'])
                throw new ApplicationException('Delivery fee has changed. Please review and confirm order.');
        }
    }

    public function estimateDeliveryAmount($delivery)
    {
        $request = $this->sendRequest('GET', 'on-demand/estimate/'.$delivery->shipday_order_id);

        return json_decode($request->getBody()->getContents(), true);
    }

    public function assignOrderCarrier(Orders_model $order)
    {
        session()->forget('shipday_delivery_id');

        $delivery = Delivery::fetchDelivery($order->order_id);
        $response = $this->assignOrderToCarrier($delivery);
        $delivery->assign_response = $response;
        $delivery->shipday_assigned_id = array_get($response, 'id');
        $delivery->tracking_url = array_get($response, 'trackingUrl');
        $delivery->status = array_get($response, 'status');
        $delivery->save();
    }

    public function getDeliveryDemandService()
    {
        $request = $this->sendRequest('GET', 'on-demand/services');

        return json_decode($request->getBody()->getContents(), true);
    }

    public function assignOrderToCarrier($delivery)
    {
        $payload = [
            "name" => $delivery->partner_name,
            "orderId" => $delivery->shipday_order_id,
            "tip" => $delivery->tip,
            "estimateReference" => $delivery['estimate_order_id'],
        ];
        $request = $this->sendRequest('POST', 'on-demand/assign', $payload);

        return json_decode($request->getBody()->getContents(), true);
    }

    public static function processStatusUpdateWebhook()
    {
        $instance = new static;

        $request = request()->input();

        if (!$instance->verifyWebhook($request))
            return new Response('Webhook failed', 400);

        Delivery::where('shipday_order_id', $request['order']['id'])->update([
            'status' => $request['order_status'],
        ]);

        return new Response('Webhook Handled', 200);
    }

    protected function createDelivery(Orders_model $order)
    {
        $model = new Delivery();
        $model->order_id = $order->order_id;
        $model->tip = $order->getOrderTotals()->firstWhere('code', 'tip')->value ?? null;
        $model->save();

        return $model;
    }

    protected function getDeliveryFeeFromOrderTotals(Orders_model $order)
    {
        $total = $order->getOrderTotals()->firstWhere('code', 'delivery');

        return $total->value ?? 0;
    }

    protected function prepareDeliveryRequest($delivery, $order): array
    {
        $collection = Geocoder::geocode($order->address->formatted_address);
        if (!$collection OR $collection->isEmpty()) {
            throw new ApplicationException(lang('igniter.local::default.alert_invalid_search_query'));
        }

        $userLocation = $collection->first();
        if (!$userLocation->hasCoordinates())
            throw new ApplicationException(lang('igniter.local::default.alert_invalid_search_query'));

        return [
            'orderNumber' => $order->order_id,
            'customerName' => $order->first_name.' '.$order->last_name,
            'customerAddress' => $order->address->formatted_address,
            'customerEmail' => $order->email,
            'customerPhoneNumber' => $order->telephone,
            'restaurantName' => $order->location->location_name,
            'restaurantAddress' => format_address($order->location->getAddress(), false),
            'restaurantPhoneNumber' => $order->location->location_telephone,
            'expectedDeliveryDate' => $delivery->expected_delivery_date,
            'expectedPickupTime' => $delivery->expected_pickup_time,
            'expectedDeliveryTime' => $delivery->expected_delivery_time,
            'pickupLatitude' => $order->location->location_lat,
            'pickupLongitude' => $order->location->location_lng,
            'deliveryLatitude' => $userLocation->getCoordinates()->getLatitude(),
            'deliveryLongitude' => $userLocation->getCoordinates()->getLongitude(),
            'tips' => $delivery->tip,
            'tax' => $delivery->tax,
            'discountAmount' => $delivery->discount,
            'deliveryFee' => $delivery->delivery_fee,
            'totalOrderCost' => $order->order_total,
            'deliveryInstruction' => $delivery->delivery_instruction,
            'orderSource' => setting('site_name').' '.$order->location->location_name,
            'additionalId' => $delivery->id,
            'clientRestaurantId' => $order->location_id,
            'paymentMethod' => $delivery->payment_method,
            'creditCardType' => '',
            'creditCardId' => '',
        ];
    }

    protected function sendRequest($method, $uri, array $payload = []): ResponseInterface
    {
        $url = $this->baseUrl.'/'.$uri;

        return (new Client())->request($method, $url, [
            'headers' => [
                'Authorization' => 'Basic '.ShipdaySettings::getApiToken(),
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);
    }

    protected function verifyWebhook($request)
    {
//        verify check

//        get delivery details
        return Delivery::where('shipday_order_id', $request['order']['id'])->first();
    }
}
