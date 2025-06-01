<?php

namespace IgniterLabs\Shipday\Classes;

use Igniter\Admin\Widgets\Form;
use Igniter\Cart\Http\Controllers\Orders;
use Igniter\Cart\Http\Requests\DeliverySettingsRequest;
use Igniter\Cart\Models\Order;
use Igniter\Local\Models\Location;
use Igniter\Local\Models\LocationSettings;
use Igniter\User\Models\User;
use IgniterLabs\Shipday\Actions\ManagesShipdayDelivery;
use IgniterLabs\Shipday\Actions\ManagesShipdayDriver;
use IgniterLabs\Shipday\Models\Settings;
use Illuminate\Support\Facades\Event;

class EventRegistry
{
    public function boot()
    {
        Order::implement(ManagesShipdayDelivery::class);
        User::implement(ManagesShipdayDriver::class);

        $this->addShipdayAttemptsTabToOrderDetailsPage();

        $this->subscribeToCreateShipdayOrderOnOrderProcessed();

        $this->subscribeToMarkShipdayOrderAsReadyForDelivery();

        $this->subscribeToAssignDriverToShipdayOrder();

        $this->extendLocationDeliverySettingsFields();
    }

    protected function addShipdayAttemptsTabToOrderDetailsPage(): void
    {
        Orders::extendFormFields(function(Form $form, $model, $context) {
            if (!$model instanceof Order
                || !$model->isDeliveryType()
                || !Settings::isConnected()
            ) return;

            $form->addTabFields([
                'shipday_logs' => [
                    'tab' => 'lang:igniterlabs.shipday::default.text_payment_logs',
                    'type' => 'datatable',
                    'useAjax' => true,
                    'defaultSort' => ['created_at', 'desc'],
                    'columns' => [
                        'created_since' => [
                            'title' => 'lang:igniter.cart::default.orders.column_time_date',
                        ],
                        'shipday_id' => [
                            'title' => 'lang:igniterlabs.shipday::default.column_shipday_id',
                        ],
                        'status' => [
                            'title' => 'lang:admin::lang.label_status',
                        ],
                        'carrier_name' => [
                            'title' => 'lang:igniterlabs.shipday::default.column_carrier',
                        ],
                        'tracking_url' => [
                            'title' => 'lang:igniterlabs.shipday::default.column_tracking_url',
                        ],
                    ],
                ],
            ]);
        });
    }

    protected function extendLocationDeliverySettingsFields(): void
    {
        Event::listen('admin.form.extendFieldsBefore', function(Form $form): void {
            if ($form->model instanceof LocationSettings && $form->model->item === 'delivery') {
                $form->fields['shipday_wait_time'] = [
                    'label' => 'lang:igniterlabs.shipday::default.label_delivery_wait_time',
                    'accordion' => 'lang:igniter.local::default.text_tab_delivery_order',
                    'type' => 'number',
                    'default' => 15,
                    'comment' => 'lang:igniterlabs.shipday::default.help_delivery_wait_time',
                ];
            }
        });

        Event::listen('system.formRequest.extendValidator', function($formRequest, $dataHolder): void {
            if ($formRequest instanceof DeliverySettingsRequest) {
                $dataHolder->attributes = array_merge($dataHolder->attributes, [
                    'shipday_wait_time' => lang('igniterlabs.shipday::default.label_delivery_wait_time'),
                ]);

                $dataHolder->rules = array_merge($dataHolder->rules, [
                    'shipday_wait_time' => ['integer', 'min:0'],
                ]);
            }
        });

        Location::extend(function(Location $model) {
            $model->addDynamicMethod('getShipdayDeliveryWaitTime', function() use ($model) {
                return $model->getSettings('delivery.shipday_wait_time', 15);
            });
        });
    }

    protected function subscribeToMarkShipdayOrderAsReadyForDelivery(): void
    {
        Event::listen('admin.statusHistory.beforeAddStatus', function($model, $object, $statusId, $previousStatus) {
            if ($object instanceof Order
                && $object->isDeliveryType()
                && $object->hasShipdayDelivery()
                && Settings::isConnected()
                && Settings::isReadyForPickupOrderStatus($statusId)
            ) {
                rescue(function() use ($object) {
                    $shipdayDelivery = $object->createOrGetShipdayDelivery();
                    if (array_get($shipdayDelivery, 'orderStatusAdmin') !== 'READY_FOR_PICKUP')
                        $object->markShipdayDeliveryAsReadyForPickup();
                });
            }
        });
    }

    protected function subscribeToAssignDriverToShipdayOrder(): void
    {
        Event::listen('admin.assignable.assigned', function($model, $assignableLog) {
            if ($model instanceof Order
                && $assignableLog->assignee
                && $model->isDeliveryType()
                && $model->hasShipdayDelivery()
                && Settings::isConnected()
                && Settings::isShipdayDriverStaffGroup($assignableLog->assignee_group_id)
            ) {
                $assignableLog->assignee->createOrGetShipdayDriver();
                $model->assignShipdayDeliveryToDriver($assignableLog->assignee);
            }
        });
    }

    protected function subscribeToCreateShipdayOrderOnOrderProcessed(): void
    {
        Event::listen('admin.order.paymentProcessed', function(Order $order) {
            rescue(function() use ($order) {
                if (!Settings::supportsOnDemandDelivery()
                    && $order->isDeliveryType()
                    && Settings::isConnected()
                ) $order->createOrGetShipdayDelivery();
            });
        });
    }
}
