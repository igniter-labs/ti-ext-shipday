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
            //            'delivery_services' => [
            //                'label' => 'lang:igniterlabs.shipday::default.label_delivery_services',
            //                'type' => 'partial',
            //                'path' => 'igniterlabs.shipday::settings.delivery_services',
            //                'comment' => 'lang:igniterlabs.shipday::default.help_delivery_services',
            //            ],
            'delivery_type' => [
                'label' => 'lang:igniterlabs.shipday::default.label_delivery_type',
                'type' => 'select',
                'default' => 'in_house',
                'options' => [
                    'on_demand' => lang('igniterlabs.shipday::default.text_on_demand'),
                    'in_house' => lang('igniterlabs.shipday::default.text_in_house'),
                ],
            ],
            'on_demand_type_option' => [
                'label' => 'lang:igniterlabs.shipday::default.label_on_demand_delivery_option',
                'type' => 'select',
                'default' => 'manually_select_delivery_service',
                'options' => [
                    'manually_select_delivery_service' => lang('igniterlabs.shipday::default.text_on_demand_manually_select'),
                    'auto_select_lowest_delivery_service' => lang('igniterlabs.shipday::default.text_on_demand_auto_select_lowest_cost'),
                ],
                'trigger' => [
                    'action' => 'show',
                    'field' => 'delivery_type',
                    'condition' => 'value[on_demand]',
                ],
                'span' => 'left',
            ],
            'delivery_services' => [
                'label' => 'lang:igniterlabs.shipday::default.label_on_demand_delivery_services',
                'type' => 'select',
                'options' => resolve(\IgniterLabs\Shipday\Classes\Client::class)->getOnDemandServices(),
                'trigger' => [
                    'action' => 'show',
                    'field' => 'delivery_type',
                    'condition' => 'value[on_demand]',
                ],
                'span' => 'right',
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
