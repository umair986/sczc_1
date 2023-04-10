# POST api/v1/order_create
## Description

This api endpoint is used to create an order. The endpoint is registered at `api/v1/` namespace. and accessible at `api/v1/order_create` url.

## Authentication

Authentication is required to access this endpoint. The API user should provide an `x-api-key` header and a `client-id` header in the request. The values for these headers should be obtained from the API provider.

## Parameters

The endpoint accepts the following parameters in the request body:

- `customer_id`: The user id of the user creating the order.
- `product_id`: The product id of the product to be ordered or variation id in case of variable product.
- `quantity`: The quantity of the product to be ordered.
- `order_note`: The order note.
- `coins`: The coins to be deducted from the user's account. If not provided, the coins will be deducted from the user's account based on the product price.
- `address`: The address of the user.
- `city`: The city of the user.
- `state`: The state of the user.
- `postal-code`: The postal code of the user.
- `country`: The country of the user.
- `phone`: The phone number of the user.

## Response

The endpoint returns the following response:

- `status_code`" The status code of the response.
- `status`: The status of the response.
- `order_id`: (when status is success) The order id of the order created.

## Status Codes

- `200`: The order is successfully created.
- `400`: Incase of any error. The message will contain the error message.
- `401`: Authentication failed. This will be the case if the `x-api-key` or `client-id` headers are missing or invalid.
- `402`: Insufficient coins in the user's account.
- `404`: Not found errors. The message will contain the error message.

## Example Request

```bash
POST /api/v1/order_create HTTP/1.1
Host: myfreebucks.com

Headers:
x-api-key: {api_key}
client-id: {client_id}

Body:
{
    "customer_id": "1",
    "product_id": "1",
    "quantity": "1",
    "order_note": "This is a test order",
    "coins": "100",
    "address": "123, Main Street",
    "city": "New York",
    "state": "New York",
    "postal-code": "10001",
    "country": "US",
    "phone": "1234567890"
}

```

## Example Response

The following is an example response from the endpoint:

```json
{
    "status_code": 200,
    "status": "success",
    "order_id": "1"
}
```