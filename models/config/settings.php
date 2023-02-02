<?php

return [
    'form' => [
        'toolbar' => [
            'buttons' => [
                'back' => [
                    'label' => 'lang:admin::lang.button_icon_back',
                    'class' => 'btn btn-outline-secondary',
                    'href' => 'settings',
                ],
                'save' => [
                    'label' => 'lang:admin::lang.button_save',
                    'class' => 'btn btn-primary',
                    'data-request' => 'onSave',
                    'data-progress-indicator' => 'admin::lang.text_saving',
                ],
            ],
        ],
        'fields' => [
            'setup' => [
                'type' => 'partial',
                'path' => '$/igniterlabs/shipday/views/settings/form/info',
            ],

            'api_key' => [
                'label' => 'lang:igniterlabs.shipday::default.label_api_key',
                'type' => 'text',
            ],
            'delivery_staff_group' => [
                'label' => 'lang:igniterlabs.shipday::default.label_delivery_staff_group',
                'type' => 'select',
                'options' => [\Admin\Models\Staff_groups_model::class, 'getDropdownOptions'],
                'comment' => 'lang:igniterlabs.shipday::default.help_delivery_staff_group',
            ],
            'ready_for_pickup_status_id' => [
                'label' => 'lang:igniterlabs.shipday::default.label_ready_for_pickup_status',
                'type' => 'select',
                'options' => ['Admin\Models\Statuses_model', 'getDropdownOptionsForOrder'],
                'comment' => 'lang:igniterlabs.shipday::default.help_ready_for_pickup_status',
            ],
            'accepted_status_id' => [
                'label' => 'lang:igniterlabs.shipday::default.label_accepted_status_id',
                'type' => 'select',
                'options' => ['Admin\Models\Statuses_model', 'getDropdownOptionsForOrder'],
                'comment' => 'lang:igniterlabs.shipday::default.help_accepted_status_id',
            ],
            'picked_up_status_id' => [
                'label' => 'lang:igniterlabs.shipday::default.label_picked_up_status_id',
                'type' => 'select',
                'options' => ['Admin\Models\Statuses_model', 'getDropdownOptionsForOrder'],
                'comment' => 'lang:igniterlabs.shipday::default.help_picked_up_status_id',
            ],
            'delivered_status_id' => [
                'label' => 'lang:igniterlabs.shipday::default.label_delivered_status',
                'type' => 'select',
                'options' => ['Admin\Models\Statuses_model', 'getDropdownOptionsForOrder'],
                'comment' => 'lang:igniterlabs.shipday::default.help_delivered_status',
            ],
            'canceled_status_id' => [
                'label' => 'lang:igniterlabs.shipday::default.label_canceled_status',
                'type' => 'select',
                'options' => ['Admin\Models\Statuses_model', 'getDropdownOptionsForOrder'],
                'comment' => 'lang:igniterlabs.shipday::default.help_canceled_status',
            ],
        ],
        'rules' => [
            'api_key' => ['required', 'string'],
            'delivery_staff_group' => ['required', 'integer'],
            'ready_for_pickup_status_id' => ['required', 'integer',
                'different:accepted_status_id',
                'different:picked_up_status_id',
                'different:delivered_status_id',
                'different:canceled_status_id',
            ],
            'accepted_status_id' => ['required', 'integer',
                'different:ready_for_pickup_status_id',
                'different:picked_up_status_id',
                'different:delivered_status_id',
                'different:canceled_status_id',
            ],
            'picked_up_status_id' => ['required', 'integer',
                'different:ready_for_pickup_status_id',
                'different:accepted_status_id',
                'different:delivered_status_id',
                'different:canceled_status_id',
            ],
            'delivered_status_id' => ['required', 'integer',
                'different:ready_for_pickup_status_id',
                'different:accepted_status_id',
                'different:picked_up_status_id',
                'different:canceled_status_id',
            ],
            'canceled_status_id' => ['required', 'integer',
                'different:ready_for_pickup_status_id',
                'different:accepted_status_id',
                'different:picked_up_status_id',
                'different:delivered_status_id',
            ],
        ],
    ],
];
