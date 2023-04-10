# POST api/v1/customer/register

## Description

This api endpoint is used to register a new customer. The endpoint is registered under the `api/v1` namespace and is accessible through the URL `/customer/register`.

## Authentication

Authentication is required to access this endpoint. The API user should provide an `x-api-key` header and a `client-id` header in the request. The values for these headers should be obtained from the API provider.

## Parameters

The endpoint accepts the following parameters in the request body:

- `voucher_no`: (required) The voucher code for the customer registration.
- `password`: (required) The password for the customer account.
- `username`: (required) The username for the customer account.
- `mobile`: (required) The mobile number of the customer.
- `email`: (required) The email address of the customer.
- `name`: (required) The name of the customer.

## HTTP Method

The endpoint only accepts HTTP POST requests.

## Response

The endpoint returns a JSON object with the following fields:

- `status_code`: The HTTP status code for the response.
- `status`: The status of the response. This will be "success" if the customer was created successfully, or "error" if there was an error creating the customer.
- `message`: A message indicating the result of the request. If the customer was created successfully, this will be "Customer created successfully". If there was an error creating the customer, this will be a list of error messages describing the cause(s) of the error.
- `user_id`: The ID of the newly created customer, if the customer was created successfully. This field will only be present if the status field is "success".

## Status Codes

The endpoint returns the following HTTP status codes:

- `200`: The customer was created successfully.
- `400`: The request was invalid. This will be the case if the request body is missing required parameters, or if the request body contains invalid parameters. 
- `401`: Authentication failed. This will be the case if the `x-api-key` or `client-id` headers are missing or invalid.

## Example Request
The following is an example request to the endpoint:

```bash
POST /api/v1/customer/register HTTP/1.1
Host: myfreebucks.com

Headers:
x-api-key: {api_key}
client-id: {client_id}

Body:
{
    "voucher_no": "ABC1234567",
    "username": "johndoe",
    "mobile" : "9123456789",
    "email": "johndoe@example.com",
    "name": "John Doe",
}

```

## Example Response

The following is an example response from the endpoint:

```json

{
    "status_code": 200,
    "status": "success",
    "message": "Customer created successfully",
    "user_id": 16
}

```