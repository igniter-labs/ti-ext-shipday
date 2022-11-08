<?php

return [
    'text_settings' => 'Shipday Delivery Settings',

    'label_cart_condition_title' => 'Shipday Delivery Fee',
    'label_cart_condition_description' => 'Shipday Delivery Fee',

    'label_api_key' => 'Shipday API Key',
    'label_sandbox_signing_secret' => 'Sandbox Signing Secret',
    'label_prod_developer_id' => 'Production Developer ID',
    'label_prod_key_id' => 'Production Key ID',
    'label_prod_signing_secret' => 'Production Signing Secret',
    'label_ready_for_pickup_status' => 'Delivery Ready for Pickup Status',
    'label_delivered_status' => 'Delivery Completed Status',
    'label_canceled_status' => 'Delivery Canceled Status',
    'label_delivery_staff_group' => 'Delivery Staff Group',

    'help_settings' => 'Configure Shipday settings',
    'help_permission' => 'Ability to manage Shipday settings',
    'help_ready_for_pickup_status' => 'When an order is updated to the chosen status, mark the shipday order as ready to pick up.',
    'help_delivered_status' => 'Select the order status to set when an order is delivered',
    'help_canceled_status' => 'Select the order status to set when a delivery is canceled',
    'help_delivery_staff_group' => 'Select the group of the staff with delivery capabilities. A corresponding carrier account will be created on Shipday.',

    'alert_distance_too_long' => 'Your delivery address is too far from the restaurant. Please choose another address.',
    'alert_delivery_address_changed' => 'Your delivery address has been changed. Please review the recalculated delivery fee.',
];
