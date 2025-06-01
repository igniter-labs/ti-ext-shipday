---
title: "Shipday Delivery"
section: "extensions"
sortOrder: 999
---

## Installation

You can install the extension via composer using the following command:

```bash
composer require igniterlabs/ti-ext-shipday -W
```

Run the database migrations to create the required tables:

```bash
php artisan igniter:up
```

## Getting started

To get started with the Shipday Delivery extension, follow these steps:

- **Signup for a Shipday account**: Visit the [Shipday website](https://www.shipday.com/) and create an account if you don't already have one.
- Navigate to the **Manage > Settings > Shipday Delivery Settings** admin page in your TastyIgniter Admin.
- The following fields are available on the Shipday Delivery Settings page:

  - **Shipday API Key**: Enter your Shipday API key here. You can find this key in your Shipday account under the integrations tab.
  - **Delivery Staff Group:** Choose the staff group for delivery staff members. Each staff member of this group will have a corresponding Shipday account created, allowing them to access the Shipday platform and manage deliveries efficiently.
  - **Delivery Ready for Pickup Status:** Select the order status that indicates an order is ready for pickup. This status will be used to trigger the delivery process in Shipday.
  - **Map Order Status to Shipday Status (and vice versa):** Choose the order statuses that will be mapped to Shipday statuses. This allows you to track the delivery status of orders in both TastyIgniter and Shipday.
- Click the **Save** button to save your settings.
Now your TastyIgniter site is integrated with Shipday Delivery, your delivery orders will be automatically sent to Shipday for dispatch and tracking.

### Marking an order as ready for pickup

To mark an order as ready for pickup, follow these steps:

- Navigate to the **Manage > Orders** admin page in your TastyIgniter Admin.
- Under the Status column, click on the status next to the order you want to mark as ready for pickup. This will open a dropdown menu with available actions.
- Select the configured **Delivery Ready for Pickup Status** status from the dropdown menu.
- The Shipday order will now be marked as ready for pickup

### Assign a delivery staff member to an order

To assign a delivery staff member to an order, follow these steps:

- Navigate to the **Manage > Orders** admin page in your TastyIgniter Admin.
- Click on the order you want to assign a delivery staff member to. This will open the order details page.
- In the order details page, locate the **Assignee** section, which is typically found in the top right section of the order details page.
- Click on the **...** text under the **Assignee** label. This will open a dialog box.
- In the dialog box, first select your configured **Delivery Staff Group** from the dropdown menu under **Assign To Group** field. This will filter the available staff members to only those in the selected group.
- Next, select the delivery staff member you want to assign to the order from the **Assign To User** dropdown menu. The dropdown will only show staff members from the selected group.
- Click the **Save** button to assign the selected delivery staff member to the order.
- The Shipday order will now be updated with the assigned delivery staff member, and they will be able to access the order in their Shipday account. If no associated Shipday Carrier account is found, a new one will be created automatically.

## Usage

This section covers how to integrate the Shipday Delivery extension into your own extension. The Shipday Delivery extension provides a simple API for managing delivery orders and staff assignments.

### Creating a Shipday Delivery Order

This extension extends the functionality of the `Igniter\Cart\Model\Order` model to include methods for creating and managing delivery orders with Shipday. To create a Shipday delivery order, you can use the `createShipdayDeliveryOrder` method provided by the `IgniterLabs\Shipday\Actions\ManagesShipdayDelivery` class.

```php
use Igniter\Cart\Models\Order;

$order = Order::find($orderId); // Replace $orderId with the actual order ID
$order->createShipdayDeliveryOrder();
```

### Editing a Shipday Delivery Order

To edit a Shipday delivery order, you can use the `editShipdayDeliveryOrder` method provided by the `Igniter\Cart\Model\Order` model. This method allows you to update the details of an existing Shipday delivery order.

```php
use Igniter\Cart\Models\Order;

$order = Order::find($orderId); // Replace $orderId with the actual order ID
$order->editShipdayDeliveryOrder([
    'delivery_address' => '123 New Address',
    'delivery_instructions' => 'Leave at the front door',
]);
```

### Updating a Shipday Delivery Order Status

To update the status of a Shipday delivery order, you can use the `updateShipdayDeliveryOrderStatus` method provided by the `Igniter\Cart\Model\Order` model. This method allows you to change the status of an existing Shipday delivery order.

```php
use Igniter\Cart\Models\Order;

$order = Order::find($orderId); // Replace $orderId with the actual order ID
$order->updateShipdayDeliveryStatus($shipdayStatus); // Replace shipdayStatus with the desired shipday status
```

### Assigning a Shipday Delivery Order to a Driver

To assign a delivery staff member to an order, you can use the `assignShipdayDeliveryToDriver` method provided by the `Igniter\Cart\Model\Order` model. This method allows you to assign a staff member to a specific Shipday delivery order.

```php
use Igniter\Cart\Models\Order;
use Igniter\User\Models\User;

$order = Order::find($orderId); // Replace $orderId with the actual order ID
$user = User::find($userId); // Replace $userId with the actual user ID of the delivery staff member
$order->assignShipdayDeliveryToDriver($order, $user);
```

### Creating a Shipday Carrier Account

To create a Shipday carrier account for a delivery staff member, you can use the `createAsShipdayDriver` method provided by the `Igniter\User\Models\User` model. This method allows you to create a new Shipday carrier account for a specific user.

```php
use Igniter\User\Models\User;
$user = User::find($userId); // Replace $userId with the actual user ID of the delivery staff member
$user->createAsShipdayDriver();
```
