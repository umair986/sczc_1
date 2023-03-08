# GET api/v1/product/{id}

## Description

This API endpoint retrieves a product by its ID. The endpoint is registered under the `api/v1` namespace and is accessible through the URL `/product/{id}`.

## Authentication
Authentication is required to access this endpoint. The API user should provide an `x-api-key` header and a `vendor-id` header in the request. The values for these headers should be obtained from the API provider.

## Parameters
The endpoint accepts a single parameter:

- `id`: the ID of the product to retrieve. This should be an integer.

## HTTP Method
The endpoint only accepts HTTP GET requests.

## Response
The response will be a JSON object with the following properties:

- `status_code`: an integer representing the HTTP status code of the response.
- `status`: a string indicating whether the request was successful or not.
- `product`: an object containing information about the product if it was found. If the product was not found, this will be an empty object.
- `message`: a string message describing any errors that occurred during the request. This will only be present if the request was unsuccessful.

## Product Object
The product object will contain the following properties:

- `id`: the ID of the product.
- `name`: the name of the product.
- `description`: a description of the product.
- `price`: the price of the product.
- `status`: the status of the product (e.g., "publish").
- `variations`: an array of variation objects representing the different variations of the product. Each variation object will contain the following properties:
  - `id`: the ID of the variation.
  - `sku`: the SKU of the variation.
  - `attributes`: an array of attribute objects representing the attributes of the variation. Each attribute object will contain the following properties:
    - `id`: the ID of the attribute.
    - `taxonomy`: the taxonomy of the attribute (e.g., "color", "size").
    - `name`: the name of the attribute (e.g., "White", "3").
    - `option`: the option value of the attribute (e.g., "white", "3").
    - `price`: the price of the variation.
    - `stock_quantity`: the stock quantity of the variation.
    - `in_stock`: a boolean indicating whether the variation is in stock.
  - `description`: a description of the variation.
  - `image`: an object representing the image of the variation. This object will contain the following properties:
    - `src`: the source URL of the image.
  - `coins`: redeemable coins for the variation.
  - `category_coins`: redeemable coins for the variation's category.
  - `brand_coins`: redeemable coins for the variation's brand.
- `stock_quantity`: the stock quantity of the product (when product doesn't have variations).
- `in_stock`: a boolean indicating whether the product is in stock (when product doesn't have variations).
- `coins`: redeemable coins for the product.
- `category_coins`: redeemable coins for the product's category.
- `brand_coins`: redeemable coins for the product's brand.
- `categories`: an array of category objects representing the categories of the product. Each category object will contain the following properties:
  - `id`: the ID of the category.
  - `name`: the name of the category.
- `images`: an array of image objects representing the images of the product. Each image object will contain the following properties:
  - `src`: the source URL of the image.

## Status Codes
The endpoint returns the following HTTP status codes:

- `200`: The request was successful, and the product information is included in the response.
- `401`: The request was not authenticated. The `x-api-key` and/or `vendor-id` headers were missing or invalid.
- `404`: The product with the specified ID was not found.

## Example Request
The following is an example request to the endpoint:

```bash
GET /v1/product/123 HTTP/1.1
Host: myfreebucks.com
x-api-key: {api_key}
vendor-id: {vendor_id}
```

## Example Response

```json
{
  "status_code": 200,
  "status": "success",
  "product": {
    "id": 123,
    "name": "My Product",
    "description": "This is my product.",
    "price": 9.99,
    "status": "publish",
    "variations": [
      {
        "id": 456,
        "sku": "123-456",
        "attributes": [
          {
            "id": 789,
            "taxonomy": "color",
            "name": "White",
            "option": "white",
            "price": 0,
            "stock_quantity": 10,
            "in_stock": true
          },
          {
            "id": 1011,
            "taxonomy": "size",
            "name": "3",
            "option": "3",
            "price": 0,
            "stock_quantity": 10,
            "in_stock": true
          }
        ],
        "description": "This is my variation.",
        "image": {
          "src": "image_url"
        }
      },
      {
        "id": 1213,
        "sku": "123-1213",
        "attributes": [
          {
            "id": 1415,
            "taxonomy": "color",
            "name": "Black",
            "option": "black",
            "price": 0,
            "stock_quantity": 10,
            "in_stock": true
          },
          {
            "id": 1617,
            "taxonomy": "size",
            "name": "3",
            "option": "3",
            "price": 0,
            "stock_quantity": 10,
            "in_stock": true
          }
        ],
        "description": "This is my variation.",
        "image": {
          "src": ""
        },
        "coins": 10,
        "category_coins": 10,
        "brand_coins": 10
      }
    ],
    "categories": [
      {
        "id": 1819,
        "name": "My Category"
      }
    ],
    "images": [
      {
        "src": "image_url"
      },
      {
        "src": "image_url"
      }
    ]
  },
}
```