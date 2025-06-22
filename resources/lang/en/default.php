<?php

return [
    'text_settings' => 'Shipday Delivery Settings',
    'text_payment_logs' => 'Shipday Updates',

    'column_shipday_id' => 'Shipday ID',
    'column_status' => 'Status',
    'column_carrier' => 'Driver',
    'column_tracking_url' => 'Tracking URL',

    'label_cart_condition_title' => 'Shipday Delivery Fee',
    'label_cart_condition_description' => 'Shipday Delivery Fee',

    'label_api_key' => 'Shipday API Key',
    'label_sandbox_signing_secret' => 'Sandbox Signing Secret',
    'label_prod_developer_id' => 'Production Developer ID',
    'label_prod_key_id' => 'Production Key ID',
    'label_prod_signing_secret' => 'Production Signing Secret',
    'label_delivery_wait_time' => 'Delivery Wait Time',
    'label_delivery_staff_group' => 'Delivery Staff Group',
    'label_ready_for_pickup_status' => 'Delivery Ready for Pickup Status',
    'label_status_map' => 'Map Order Status to Shipday Status (and vice versa)',
    'label_order_status' => 'Order Status',
    'label_shipday_status' => 'Shipday Status',
    'label_accepted_status' => 'Accepted',
    'label_started_status' => 'Started',
    'label_picked_up_status' => 'Picked Up',
    'label_ready_to_deliver_status' => 'Ready to Deliver',
    'label_delivered_status' => 'Delivered',
    'label_incomplete_status' => 'Incomplete',
    'label_failed_delivery_status' => 'Failed Delivery',

    'help_settings' => 'Configure Shipday settings',
    'help_permission' => 'Ability to manage Shipday settings',
    'help_delivery_wait_time' => ' Set in minutes the average time it takes an order to be ready for delivery. This is used to calculate the estimated delivery pick up time.',
    'help_status_map' => 'Define how your order statuses and Shipday statuses correspond to each other. When an order status is updated, the linked Shipday status will be triggered, and updates from Shipday will automatically reflect in your order status. This ensures seamless synchronization between your system and Shipday.',
    'help_ready_for_pickup_status' => 'When an order is updated to the chosen status, mark the shipday order as ready to pick up. You can add new status under Manage > Settings > Statuses.',
    'help_delivery_staff_group' => 'Select the staff group with delivery capabilities. Staff in selected group will have a corresponding carrier account created on Shipday if none already exists with the same email address.',

    'alert_distance_too_long' => 'Your delivery address is too far from the restaurant. Please choose another address.',
    'alert_delivery_address_changed' => 'Your delivery address has been changed. Please review the recalculated delivery fee.',
];
