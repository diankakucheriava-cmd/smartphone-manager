# Smartphone Product Manager API

A RESTful Laravel application for managing a local smartphone catalog.

The application imports smartphone data from the DummyJSON API, stores it in a relational database, exposes CRUD endpoints, supports filtering and pagination, and converts product prices from USD to UAH or EUR using exchange rates from the National Bank of Ukraine.

## Features

- Import smartphones from DummyJSON through an idempotent Artisan command
- Create, read, partially update, and delete products
- Filter products by brand
- Paginate product results with a configurable page size
- Return product prices in USD, UAH, or EUR
- Cache NBU exchange rates for one day
- Retry failed external HTTP requests
- Use configurable fallback exchange rates when NBU is unavailable
- Validate both API requests and third-party import payloads
- Keep multi-table writes atomic with database transactions
- Cover the API and currency logic with automated PHPUnit tests

## Requirements

- PHP 8.4+
- Composer
- SQLite

## Installation

Clone the repository and install the dependencies:

```bash
git clone https://github.com/CorpEdward/Middle-PHP-backend-assignment.git
cd smartphone-manager
```

Install dependencies:
```bash
composer install
```

Create the environment file and generate the application key:

```bash
cp .env.example .env
```
Create the SQLite database:

```bash
touch database/database.sqlite
php artisan key:generate
```

Run the migrations:

```bash
php artisan migrate
```

Start the local development server:

```bash
php artisan serve
```

The API will be available at:

```text
http://127.0.0.1:8000/api
```

## Initial Product Import

Import all smartphone products from DummyJSON with:

```bash
php artisan products:seed
```

The command is idempotent. Imported products are matched by `external_id`, so running the command again updates existing imported products instead of creating duplicates.

Each product is imported inside its own database transaction. This prevents partially saved products when creating or synchronizing related tags, images, or reviews fails.

Before any database write, the external payload is validated by `ProductImportValidator`. Invalid third-party data stops the import instead of being silently stored as incomplete data.

## API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/products` | Return a paginated list of products |
| `GET` | `/api/products/{id}` | Return one product by its local database ID |
| `POST` | `/api/products` | Create a new local product |
| `PATCH` | `/api/products/{id}` | Partially update an existing product |
| `DELETE` | `/api/products/{id}` | Delete a product |

### Product list query parameters

`GET /api/products` supports:

| Parameter | Description |
|---|---|
| `page` | Page number |
| `limit` | Number of products per page |
| `brand` | Filter by brand name |
| `currency` | Output currency: `USD`, `EUR`, or `UAH` |

Example:

```http
GET /api/products?brand=Apple&limit=5&page=1&currency=EUR
```

### Single product currency conversion

```http
GET /api/products/1?currency=UAH
```

When the `currency` parameter is omitted, prices are returned in USD.

## Example Create Request

```http
POST /api/products
Content-Type: application/json
```

```json
{
  "title": "Samsung Galaxy S25",
  "description": "Latest Samsung flagship smartphone.",
  "category": "smartphones",
  "price": 1299.99,
  "discountPercentage": 10.5,
  "rating": 4.8,
  "stock": 35,
  "brand": "Samsung",
  "sku": "SMA-SAM-S25-001",
  "weight": 210,
  "dimensions": {
    "width": 76.4,
    "height": 162.3,
    "depth": 8.2
  },
  "warrantyInformation": "2 years warranty",
  "shippingInformation": "Ships within 2 business days",
  "availabilityStatus": "In Stock",
  "returnPolicy": "30 days return policy",
  "minimumOrderQuantity": 1,
  "meta": {
    "barcode": "1234567890123",
    "qrCode": "https://example.com/qr/s25"
  },
  "thumbnail": "https://example.com/images/s25-thumbnail.webp",
  "images": [
    "https://example.com/images/s25-1.webp",
    "https://example.com/images/s25-2.webp"
  ],
  "tags": [
    "smartphones",
    "android",
    "samsung"
  ],
  "reviews": [
    {
      "rating": 5,
      "comment": "Amazing phone!",
      "date": "2026-01-01T10:00:00Z",
      "reviewerName": "John Doe",
      "reviewerEmail": "john@example.com"
    }
  ]
}
```

Local products do not have a third-party identifier, so their `external_id` is stored as `null`.

## Partial Updates

`PATCH` only updates fields present in the request body.

Example:

```http
PATCH /api/products/1
Content-Type: application/json
```

```json
{
  "price": 999.99,
  "dimensions": {
    "width": 75.8
  }
}
```

Fields that are not present remain unchanged.

For relations:

- Omitting `tags`, `images`, or `reviews` leaves the current relation unchanged.
- Sending an empty array removes all related entries for that relation.
- Sending a non-empty array replaces the current relation data with the supplied values.

## Multi-Currency Support

All product prices are stored in USD.

Exchange rates are fetched from the National Bank of Ukraine and cached under the `nbu_exchange_rates` cache key for one day.

The service uses the following conversion rules:

```text
USD → USD: original amount
USD → UAH: amount × USD rate
USD → EUR: amount × USD rate ÷ EUR rate
```

The NBU API returns the UAH value of one unit of each foreign currency, so EUR conversion is calculated through UAH as a cross-rate.

The HTTP client uses:

- a request timeout;
- three attempts with a delay between attempts;
- cached rates to avoid requesting NBU on every API call.

If NBU is unavailable or does not return both required rates, the application uses fallback values from `config/currency.php`:

```php
return [
    'fallback_usd_rate' => 41.5,
    'fallback_eur_rate' => 45.0,
];
```

Fallback rates are only a resilience mechanism and are not treated as live market rates.

## Database Schema

### `products`

Stores the main product data.

| Column | Description |
|---|---|
| `id` | Local primary key |
| `external_id` | Nullable unique identifier from DummyJSON |
| `title` | Product title |
| `description` | Product description |
| `category_id` | Foreign key to `categories` |
| `brand_id` | Foreign key to `brands` |
| `price` | Base price stored in USD |
| `discount_percentage` | Discount percentage |
| `rating` | Product rating |
| `stock` | Available quantity |
| `sku` | Unique stock keeping unit |
| `weight` | Product weight |
| `width` | Product width |
| `height` | Product height |
| `depth` | Product depth |
| `warranty_information` | Warranty description |
| `shipping_information` | Shipping description |
| `availability_status` | Availability value represented by `AvailabilityStatus` enum |
| `return_policy` | Return policy description |
| `minimum_order_quantity` | Minimum purchasable quantity |
| `barcode` | Product barcode |
| `qr_code` | QR code URL |
| `thumbnail` | Thumbnail URL |
| `created_at`, `updated_at` | Laravel timestamps |

### `brands`

| Column | Description |
|---|---|
| `id` | Primary key |
| `name` | Unique brand name |
| `created_at`, `updated_at` | Laravel timestamps |

Relationship: one brand has many products.

### `categories`

| Column | Description |
|---|---|
| `id` | Primary key |
| `name` | Unique category name |
| `created_at`, `updated_at` | Laravel timestamps |

Relationship: one category has many products.

### `tags`

| Column | Description |
|---|---|
| `id` | Primary key |
| `name` | Unique tag name |
| `created_at`, `updated_at` | Laravel timestamps |

Products and tags have a many-to-many relationship.

### `product_tag`

Pivot table connecting products and tags.

| Column | Description |
|---|---|
| `product_id` | Foreign key to `products` |
| `tag_id` | Foreign key to `tags` |

### `images`

| Column | Description |
|---|---|
| `id` | Primary key |
| `product_id` | Foreign key to `products` |
| `url` | Image URL |
| `created_at`, `updated_at` | Laravel timestamps |

Relationship: one product has many images.

### `reviews`

| Column | Description |
|---|---|
| `id` | Primary key |
| `product_id` | Foreign key to `products` |
| `rating` | Review rating |
| `comment` | Review text |
| `reviewed_at` | Original review date |
| `reviewer_name` | Reviewer name |
| `reviewer_email` | Reviewer email |
| `created_at`, `updated_at` | Laravel timestamps |

Relationship: one product has many reviews.

### Relationship overview

```text
Brand    1 ─── * Product * ─── 1 Category
                    │
                    ├── * Image
                    ├── * Review
                    └── * ─── * Tag
```

Deleting a product removes its images, reviews, and pivot rows. Shared tags, brands, and categories are preserved.

## Application Structure

The application is organized by responsibility rather than placing all logic in controllers.

### Controllers

`ProductController` handles HTTP input and output. It delegates business operations to services and returns `ProductResource` responses.

### Form Requests

- `IndexProductRequest` validates pagination, brand, and currency query parameters.
- `ShowProductRequest` validates the currency query parameter for a single product.
- `StoreProductRequest` validates product creation payloads.
- `UpdateProductRequest` validates partial updates using `sometimes` rules.

### Resources

`ProductResource` defines the public JSON representation and prevents internal fields such as `brand_id`, `category_id`, and pivot metadata from being exposed.

### Services

- `ProductService` creates and partially updates local products inside database transactions.
- `ProductImportService` fetches, validates, maps, and imports DummyJSON products.
- `ProductRelationService` contains shared synchronization logic for tags, images, and reviews.
- `CurrencyService` handles rate retrieval, caching, fallback behavior, and conversion.

### Validation

`ProductImportValidator` validates third-party DummyJSON payloads before any database operation. It is separate from Laravel Form Requests because the data is not an incoming application HTTP request.

### Enums

- `Currency` defines the supported output currencies.
- `AvailabilityStatus` defines valid product availability states.

### Models

Eloquent models define the relationships between products, brands, categories, tags, images, and reviews.

## Error Handling

- Invalid request data returns `422 Unprocessable Entity`.
- Missing products return `404 Not Found` through route model binding.
- External HTTP failures during import cause the command to fail clearly.
- Invalid third-party product payloads are rejected before database writes.
- Transactions prevent partial product and relation updates.
- NBU failures use configured fallback exchange rates.

## Testing

The project uses PHPUnit with Laravel's testing utilities.

The test suite covers:

- product listing and pagination;
- brand filtering;
- currency conversion;
- product retrieval and 404 responses;
- product creation and validation;
- partial product updates;
- relation synchronization and clearing;
- product deletion and cascading behavior;
- shared relation preservation;
- exchange-rate caching and fallback behavior.

All tests pass with:

```bash
php artisan test
```

## Main Design Decisions

- Prices are stored only in USD to keep one canonical database value.
- Converted prices are calculated at response time and are not persisted.
- Each imported product is processed in its own transaction so one product cannot be partially saved.
- External data is validated before a transaction begins.
- Local and imported product workflows are separate because they have different validation and default-value rules.
- Shared relation synchronization is extracted into one service because it is used by both local CRUD and import workflows.
- Route model binding is used for product lookup and automatic 404 responses.
- `external_id` is nullable because locally created products do not belong to an external system.
