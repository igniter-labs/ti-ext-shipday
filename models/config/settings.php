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
            'assigned_status_id' => [
                'label' => 'lang:igniterlabs.shipday::default.label_assigned_status',
                'type' => 'select',
                'options' => ['Admin\Models\Statuses_model', 'getDropdownOptionsForOrder'],
                'comment' => 'lang:igniterlabs.shipday::default.help_assigned_status',
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
            'assigned_status_id' => ['required', 'different:delivered_status_id', 'different:canceled_status_id'],
            'delivered_status_id' => ['required', 'different:canceled_status_id', 'different:assigned_status_id'],
            'canceled_status_id' => ['required', 'different:assigned_status_id', 'different:delivered_status_id'],
        ],
    ],
];
