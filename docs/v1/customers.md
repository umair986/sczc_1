# GET api/v1/customers

## Description

This API endpoint retrieves a list of products based on the specified query parameters. The endpoint is registered under the `api/v1` namespace and is accessible through the URL `/customers`.

## Authentication
Authentication is required to access this endpoint. The API user should provide an `x-api-key` header and a `client-id` header in the request. The values for these headers should be obtained from the API provider.

## Query Parameters
The following query parameters are available:

- `customer`: (Optional) The username or email of the customer to be retrieved. If this parameter is not specified, all customers will be retrieved.
- `page`: (Optional) The page number of the results to be retrieved. Default is 1.
- `limit`: (Optional) The number of results to be retrieved per page. Default is 10.

## Response

The response will be a JSON object with the following properties:

- `status_code`: an integer representing the HTTP status code of the response.
- `status`: a string indicating the status of the response. This will be "success" if the request was successful, or "error" if there was an error.
- `page`: an integer representing the page number of the results.
- `customer_count`: an integer representing the total number of customers.
- `customers`: an array of objects representing the customers.

## Customer Object

The customer object will have the following properties:

- `id`: an integer representing the ID of the customer.
- `username`: a string representing the username of the customer.
- `email`: a string representing the email address of the customer.
- `display_name`: a string representing the display name of the customer.
- `avatar`: a string representing the URL of the customer's avatar.
- `points`: a string representing the number of points the customer has.
- `total_orders`: an integer representing the total number of orders placed by the customer.

## Status Codes

The following status codes may be returned:

- `200`: The request was successful.
- `401`: Authentication failed. The API user did not provide a valid `x-api-key` or `client-id` header.

## Example Request

```bash
GET /api/v1/customers?customer=johndoe HTTP/1.1
Host: myfreebucks.com
x-api-key: {api_key}
client-id: {client_id}
```

## Example Response

```json
{
    "status_code": 200,
    "status": "success",
    "page": 1,
    "customer_count": 1,
    "customers": [
        {
            "id": 15,
            "username": "johndoe",
            "email": "johndoe@example.com",
            "display_name": "John Doe",
            "avatar": "avatar_url",
            "points": "100",
            "total_orders": 1
        }
    ]
}
```
