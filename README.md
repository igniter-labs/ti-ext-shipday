TastyIgniter integrates with Shipday, allowing you to manage local delivery orders with an all-in-one local dispatch and
delivery tracking system. Delivery orders can be quickly imported into Shipday's dispatch system for simple delivery
management. Using Google Maps, you can quickly define route clusters on the map to optimise delivery stops and follow
delivery drivers in real time.

From a single platform, you can use your own delivery personnel or make use of outside delivery services like **
DoorDash** and **Uber** (only accessible in the US).

Shipday has a direct integration with TastyIgniter. Any delivery order a customer places on your TastyIgniter website
will instantly be forwarded to Shipday for dispatch and delivery tracking.

### Configuration

- Go to **System > Settings > Shipday Delivery Settings** to enter your API Key (follow instructions on the page)
- For On-Demand Delivery using 3rd party providers, disable the default `delivery` Cart Condition and enable
  the `shipday` Cart Condition under **System > Settings > Cart Settings**
- Enable **Reject Orders Outside Delivery Area** under **System > Settings > Sales**. This will require customers to
  enter their delivery address before placing an order.
