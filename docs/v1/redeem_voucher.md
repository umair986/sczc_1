# POST api/v1/redeem_voucher

## Description

This api endpoint is used to redeem voucher code. The endpoint is registered at `api/v1/` namespace. and accessible at `api/v1/redeem_voucher` url.

## Authentication

Authentication is required to access this endpoint. The API user should provide an `x-api-key` header and a `client-id` header in the request. The values for these headers should be obtained from the API provider.

## Parameters

The endpoint accepts the following parameters in the request body:

- `voucher_no`: (Required) The voucher code to be redeemed.
- `customer_id`: (Required) The customer id of the user redeeming the voucher.

## Response

The endpoint returns the following response:

- `status_code`" The status code of the response.
- `status`: The status of the response.
- `message`: The message of the response.
- `data`: (when status is success) An object containing the following data:
  - `balance`: The balance of the user after the voucher is redeemed.

## Status Codes

- `200`: The voucher is successfully redeemed.
- `400`: Incase of any error. The message will contain the error message.
- `401`: Authentication failed. This will be the case if the `x-api-key` or `client-id` headers are missing or invalid.

## Example Request

```bash
POST /api/v1/redeem_voucher HTTP/1.1
Host: myfreebucks.com

Headers:
x-api-key: {api_key}
client-id: {client_id}

Body:
{
    "voucher_no": "TES030036C8C",
    "customer_id": "1"
}

```

## Example Response

The following is an example response from the endpoint when the voucher is successfully redeemed:

```json
{
    "status_code": 200,
    "status": "success",
    "message": "Yay! Your account has been credited with 100 of coins!",
    "data": {
        "balance": 350
    }
}
``` 