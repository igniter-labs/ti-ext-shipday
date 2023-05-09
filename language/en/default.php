<?php

return [
    'text_settings' => 'Shipday Delivery Settings',
    'text_payment_logs' => 'Shipday Updates',

    'column_shipday_id' => 'Shipday ID',
    'column_status' => 'Status',
    'column_carrier_id' => 'Carrier ID',
    'column_tracking_url' => 'Tracking URL',

    'label_cart_condition_title' => 'Shipday Delivery Fee',
    'label_cart_condition_description' => 'Shipday Delivery Fee',

    'label_api_key' => 'Shipday API Key',
    'label_sandbox_signing_secret' => 'Sandbox Signing Secret',
    'label_prod_developer_id' => 'Production Developer ID',
    'label_prod_key_id' => 'Production Key ID',
    'label_prod_signing_secret' => 'Production Signing Secret',
    'label_delivery_wait_time' => 'Delivery Wait Time',
    'label_ready_for_pickup_status' => 'Delivery Ready for Pickup Status',
    'label_accepted_status_id' => 'Delivery Accepted Status',
    'label_picked_up_status_id' => 'Delivery Picked Up Status',
    'label_delivered_status' => 'Delivery Completed Status',
    'label_canceled_status' => 'Delivery Canceled Status',
    'label_delivery_staff_group' => 'Delivery Staff Group',

    'help_settings' => 'Configure Shipday settings',
    'help_permission' => 'Ability to manage Shipday settings',
    'help_delivery_wait_time' => ' Set in minutes the average time it takes an order to be ready for delivery. This is used to calculate the estimated delivery pick up time.',
    'help_ready_for_pickup_status' => 'When an order is updated to the chosen status, mark the shipday order as ready to pick up.',
    'help_accepted_status_id' => 'Select the order status to set when an order is marked as accepted on shipday',
    'help_picked_up_status_id' => 'Select the order status to set when a delivery is marked as picked up on shipday',
    'help_delivered_status' => 'Select the order status to set when a delivery is marked as delivered on shipday',
    'help_canceled_status' => 'Select the order status to set when a delivery is marked as canceled on shipday',
    'help_delivery_staff_group' => 'Select the staff group with delivery capabilities. Staff in selected group will have a corresponding carrier account created on Shipday if none already exists with the same email address.',

    'alert_distance_too_long' => 'Your delivery address is too far from the restaurant. Please choose another address.',
    'alert_delivery_address_changed' => 'Your delivery address has been changed. Please review the recalculated delivery fee.',
];
