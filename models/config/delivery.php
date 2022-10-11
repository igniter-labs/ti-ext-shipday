<?php

return [
    'list' => [
        'filter' => [],
        'toolbar' => [
            'buttons' => [
                'delete' => [
                    'label' => 'lang:admin::lang.button_delete',
                    'class' => 'btn btn-danger',
                    'data-attach-loading' => '',
                    'data-request-form' => '#list-form',
                    'data-request' => 'onDelete',
                    'data-request-data' => "_method:'DELETE'",
                    'data-request-confirm' => 'lang:admin::lang.alert_warning_confirm',
                ],
            ],
        ],
        'columns' => [
            'tracking_url' => [
                'label' => 'lang:igniterlabs.foodonline::default.delivery.column_tracking_url',
                'type' => 'button',
                'iconCssClass' => 'fa fa-map-marker',
                'attributes' => [
                    'class' => 'btn btn-edit',
                    'url' => '{tracking_url}',
                ],
            ],
            'order_id' => [
                'label' => 'lang:igniterlabs.foodonline::default.delivery.column_order_id',
            ],
            'fee' => [
                'label' => 'lang:igniterlabs.foodonline::default.delivery.column_fee',
            ],
            'pickup_time' => [
                'label' => 'lang:igniterlabs.foodonline::default.delivery.column_pickup_time',
            ],
            'delivery_time' => [
                'label' => 'lang:igniterlabs.foodonline::default.delivery.column_delivery_time',
            ],
            'pickup_duration' => [
                'label' => 'lang:igniterlabs.foodonline::default.delivery.column_pickup_duration',
            ],
            'delivery_duration' => [
                'label' => 'lang:igniterlabs.foodonline::default.delivery.column_delivery_duration',
            ],
            'estimate_order_id' => [
                'label' => 'lang:igniterlabs.foodonline::default.delivery.column_estimate',
                'invisible' => true,
            ],
            'status' => [
                'label' => 'lang:igniterlabs.foodonline::default.delivery.column_status',
            ],
        ],
    ],
    'form' => [
        'toolbar' => [
            'buttons' => [
                'save' => [
                    'label' => 'lang:admin::lang.button_save',
                    'partial' => 'form/toolbar_save_button',
                    'class' => 'btn btn-primary',
                    'data-request' => 'onSave',
                    'data-progress-indicator' => 'lang:admin::lang.text_saving',
                ],
//                'delete' => [
//                    'label' => 'lang:admin::lang.button_icon_delete', 'class' => 'btn btn-danger',
//                    'data-request' => 'onDelete', 'data-request-data' => "_method:'DELETE'",
//                    'data-progress-indicator' => 'lang:admin::lang.text_deleting',
//                    'data-request-confirm' => 'lang:admin::lang.alert_warning_confirm', 'context' => ['edit'],
//                ],
            ],
        ],
        'fields' => [

        ],
        'tabs' => [
            'fields' => [],
        ],
    ],
];
