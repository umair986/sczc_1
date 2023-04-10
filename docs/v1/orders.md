# GET api/v1/orders

## Description

This API endpoint retrieves a product by its ID. The endpoint is registered under the `api/v1` namespace and is accessible through the URL `/orders`.

## Authentication
Authentication is required to access this endpoint. The API user should provide an `x-api-key` header and a `client-id` header in the request. The values for these headers should be obtained from the API provider.

## Parameters
The endpoint accepts a single parameter:

- `order_id`: the ID of the order to retrieve. This should be an integer. If not provided, all orders will be returned.
- `status`: the status of the order to retrieve. Options are: `pending`, `processing`, `on-hold`, `completed`, `cancelled`, `refunded`, `failed`. If not provided, all orders will be returned.
- `limit`: the number of orders to retrieve. Default is 10.
- `page`: the page of orders to retrieve. Default is 1.
- `orderby`: the field to order the orders by. Options are: `date`, `id`, `title`. Default is `date`.
- `sortby`: the order to sort the orders by. Options are: `asc`, `desc`. Default is `desc`.

## Response
The response will be a JSON object with the following properties:

- `status_code`: an integer representing the HTTP status code of the response.
- `status`: a string indicating whether the request was successful or not.
- `page`: an integer representing the requested page number.
- `order_count`: an integer representing the number of orders returned in the response.
- `orders`: an array of objects containing information about the orders if they were found. If no orders were found, this will be an empty array.
- `message`: a string message describing any errors that occurred during the request. This will only be present if the request was unsuccessful.

## Order Object

The order object will contain the following properties:


- `id`: the ID of the order.
- `customer_id`: the ID of the customer who placed the order.
- `status`: the status of the order.
- `order_time`: the date and time the order was placed.
- `items`: an array of objects containing information about the items in the order.
- `shipping_address`: an object containing information about the shipping address.
- `billing_address`: an object containing information about the billing address.
- `coins`: the number of coins redeemed for the order.
- `coins_status`: the status of the coins redeemed for the order.
- `total`: the total amount of the order.

## Item Object

The item object will contain the following properties:

- `id`: the ID of the item.
- `name`: the name of the item.
- `product_id`: the ID of the product the item belongs to.
- `quantity`: the quantity of the item.
- `subtotal`: the subtotal of the item.
- `total`: the total of the item.
- `attributes`: an object containing information about the attributes of the item.

## Address Object

The address object will contain the following properties:

- `name`: the first name of the customer.
- `address`: the address of the customer.
- `city`: the city of the customer.
- `state`: the state of the customer.
- `postcode`: the postcode of the customer.
- `country`: the country of the customer.
- `email`: the email of the customer.
- `phone`: the phone of the customer.

## Example Request

```bash
GET /api/v1/orders/?limit=2&page=5 HTTP/1.1
Host: myfreebucks.com
x-api-key: {api_key}
client-id: {client_id}
```

## Example Response

```json
{
    "status_code": 200,
    "status": "success",
    "page": 5,
    "order_count": 2,
    "orders": [
        {
            "id": 123,
            "customer_id": 456,
            "status": "completed",
            "order_time": "2018-01-01 00:00:00",
            "items": [
                {
                    "id": 789,
                    "name": "Product 1",
                    "product_id": 101,
                    "quantity": 1,
                    "subtotal": 10,
                    "total": 10,
                    "attributes": {
                        "size": "small",
                        "color": "red"
                    }
                },
                {
                    "id": 102,
                    "name": "Product 2",
                    "product_id": 103,
                    "quantity": 1,
                    "subtotal": 20,
                    "total": 20,
                    "attributes": {
                        "size": "medium",
                        "color": "blue"
                    }
                }
            ],
            "shipping_address": {
                "name": "John Doe",
                "address": "123 Main St",
                "city": "New York",
                "state": "NY",
                "postcode": "10001",
                "country": "US",
            },
            "billing_address": {
                "name": "John Doe",
                "address": "123 Main St",
                "city": "New York",
                "state": "NY",
                "postcode": "10001",
                "country": "US",
                "email": "example@example.com",
                "phone": "555-555-5555",
            },
            "coins": 100,
            "coins_status": "redeemed",
            "total": 300
        }
    ],
}

```
