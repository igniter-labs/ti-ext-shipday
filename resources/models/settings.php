<?php

use IgniterLabs\Shipday\Models\Settings;

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
            'delivery_type' => [
                'label' => 'lang:igniterlabs.shipday::default.label_delivery_type',
                'type' => 'select',
                'default' => 'in_house',
                'options' => [
                    'on_demand' => lang('igniterlabs.shipday::default.text_on_demand_delivery'),
                    'in_house' => lang('igniterlabs.shipday::default.text_in_house_delivery'),
                ],
            ],
            'on_demand_type_option' => [
                'label' => 'lang:igniterlabs.shipday::default.label_on_demand_delivery_option',
                'type' => 'select',
                'default' => 'auto_select_lowest_fee',
                'options' => [
                    'manual_selection' => lang('igniterlabs.shipday::default.text_on_demand_manually_select'),
                    'auto_select_lowest_fee' => lang('igniterlabs.shipday::default.text_on_demand_auto_select_lowest_fee'),
                    'auto_select_highest_fee' => lang('igniterlabs.shipday::default.text_on_demand_auto_select_highest_fee'),
                ],
                'trigger' => [
                    'action' => 'show',
                    'field' => 'delivery_type',
                    'condition' => 'value[on_demand]',
                ],
                'span' => 'left',
            ],
            'delivery_service' => [
                'label' => 'lang:igniterlabs.shipday::default.label_on_demand_delivery_services',
                'type' => 'select',
                'options' => Settings::getDeliveryServiceOptions(),
                'trigger' => [
                    'action' => 'show',
                    'field' => 'on_demand_type_option',
                    'condition' => 'value[manual_selection]',
                ],
                'span' => 'right',
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
            'delivery_type' => ['required', 'in:on_demand,in_house'],
            'on_demand_type_option' => ['nullable', 'required_if:delivery_type,on_demand', 'in:manual_selection,auto_select_lowest_fee'],
            'delivery_service' => ['nullable', 'required_if:on_demand_type_option,manual_selection', 'string'],
        ],
    ],
];
