# GET api/v1/products

## Description

This API endpoint retrieves a list of products based on the specified query parameters. The endpoint is registered under the `api/v1` namespace and is accessible through the URL `/products/`.

## Authentication
Authentication is required to access this endpoint. The API user should provide an `x-api-key` header and a `vendor-id` header in the request. The values for these headers should be obtained from the API provider.

## Query Parameters
The following query parameters are available:

- `limit`: (optional) The number of products to retrieve. This should be an integer. The default value is `10`.
- `page`: (optional) The page number to retrieve. This should be an integer. The default value is `1`.
- `category`: (optional) The category of the products to retrieve. This should be a string.
- `orderby`: (optional) The field to order the products by. This should be one of the following values: `id`, `title`, `popularity`, `rating`, or `price`. The default value is `title`.
- `order`: (optional) The order to sort the products by. This should be one of the following values: `asc` or `desc`. The default value is `asc`.

## HTTP Method
The endpoint only accepts HTTP GET requests.

## Response
The response will be a JSON object with the following properties:

- `status_code`: an integer representing the HTTP status code of the response.
- `status`: a string indicating whether the request was successful or not.
- `page`: an integer representing the requested page number.
- `product_count`: an integer representing the number of products returned in the response.
- `total_products`: a string representing the total number of products available in the specified category.
- `products`: an array of objects containing information about the products if they were found. If no products were found, this will be an empty array.
- `message`: a string message describing any errors that occurred during the request. This will only be present if the request was unsuccessful, and will be "No products found" in case of no products found.

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
- `401`: The request was not authenticated. The x-api-key and/or vendor-id headers were missing or invalid.
- `404`: The request was successful, but no products were found.

## Example Request
The following is an example request to the endpoint:

```bash
GET /api/v1/products/?limit=2&page=5 HTTP/1.1
Host: myfreebucks.com
x-api-key: {api_key}
vendor-id: {vendor_id}
```

## Example Response

```json
{
  "status_code": 200,
  "status": "success",
  "page": "5",
  "product_count": 2,
  "total_products": "2034",
  "products": [
    {
      "id": 7896,
      "name": "Blue T-Shirt",
      "description": "A simple blue t-shirt",
      "price": "29.99",
      "status": "publish",
      "categories": [
        {
          "id": 123,
          "name": "Clothing"
        },
        {
          "id": 130,
          "name": "Men's Clothing"
        }
      ],
      "images": [
        {
          "src": "image_url"
        }
      ],
      "variations": [
        {
          "id": 7898,
          "sku": "BLUE-TSHIRT-001",
          "attributes": [
            {
              "id": 412,
              "taxonomy": "size",
              "name": "Small",
              "option": "S"
            },
            {
              "id": 419,
              "taxonomy": "color",
              "name": "Blue",
              "option": "Blue"
            }
          ],
          "description": "Small Blue T-Shirt",
          "price": "29.99",
          "stock_quantity": 10,
          "in_stock": true,
          "image": {
            "src": "image_url"
          }
        }
      ]
    },
    {
      "id": 7897,
      "name": "Red Hoodie",
      "description": "A cozy red hoodie",
      "price": "49.99",
      "status": "publish",
      "categories": [
        {
          "id": 123,
          "name": "Clothing"
        },
        {
          "id": 130,
          "name": "Men's Clothing"
        }
      ],
      "images": [
        {
          "src": "image_url"
        }
      ],
      "variations": [
        {
          "id": 7899,
          "sku": "RED-HOODIE-001",
          "attributes": [
            {
              "id": 412,
              "taxonomy": "size",
              "name": "Large",
              "option": "L"
            },
            {
              "id": 419,
              "taxonomy": "color",
              "name": "Red",
              "option": "Red"
            }
          ],
          "description": "Large Red Hoodie",
          "price": "49.99",
          "stock_quantity": 5,
          "in_stock": true,
          "image": {
            "src": "image_url"
          }
        }
      ]
    }
  ]
}
``` 