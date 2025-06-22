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
                'path' => 'igniterlabs.shipday::settings.info',
            ],

            'api_key' => [
                'label' => 'lang:igniterlabs.shipday::default.label_api_key',
                'type' => 'text',
            ],
            'delivery_staff_group' => [
                'label' => 'lang:igniterlabs.shipday::default.label_delivery_staff_group',
                'type' => 'select',
                'options' => [\Igniter\User\Models\UserGroup::class, 'getDropdownOptions'],
                'comment' => 'lang:igniterlabs.shipday::default.help_delivery_staff_group',
            ],
            'ready_for_pickup_status_id' => [
                'label' => 'lang:igniterlabs.shipday::default.label_ready_for_pickup_status',
                'type' => 'select',
                'options' => [\Igniter\Admin\Models\Status::class, 'getDropdownOptionsForOrder'],
                'comment' => 'lang:igniterlabs.shipday::default.help_ready_for_pickup_status',
            ],
            'status_map' => [
                'label' => 'lang:igniterlabs.shipday::default.label_status_map',
                'type' => 'repeater',
                'commentAbove' => 'lang:igniterlabs.shipday::default.help_status_map',
                'form' => [
                    'fields' => [
                        'shipday_status' => [
                            'label' => 'lang:igniterlabs.shipday::default.label_shipday_status',
                            'type' => 'select',
                        ],
                        'order_status' => [
                            'label' => 'lang:igniterlabs.shipday::default.label_order_status',
                            'type' => 'select',
                            'options' => [\Igniter\Admin\Models\Status::class, 'getDropdownOptionsForOrder'],
                        ],
                    ],
                ],
            ],
        ],
        'rules' => [
            'api_key' => ['required', 'string'],
            'delivery_staff_group' => ['required', 'integer'],
            'ready_for_pickup_status_id' => ['required', 'integer'],
            'status_map' => ['required', 'array'],
            'status_map.*.shipday_status' => ['required', 'string'],
            'status_map.*.order_status' => ['required', 'integer'],
        ],
    ],
];
