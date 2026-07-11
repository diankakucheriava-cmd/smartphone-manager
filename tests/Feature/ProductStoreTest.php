<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStoreTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title'    => 'Samsung Galaxy S25',
            'category' => 'smartphones',
            'availabilityStatus' => 'In Stock',
            'price'    => 999.99,
            'brand'    => 'Samsung',
            'sku'      => 'SAM-S25-001',
            'stock'    => 10,
        ], $overrides);
    }

    public function test_creates_product_from_valid_payload(): void
    {
        $this->postJson('/api/products', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('title', 'Samsung Galaxy S25')
            ->assertJsonPath('price', 999.99);

        $this->assertDatabaseHas('products', [
            'sku' => 'SAM-S25-001',
            'availability_status' => 'In Stock',
        ]);
    }

    public function test_creates_new_brand_when_not_exists(): void
    {
        $this->postJson('/api/products', $this->validPayload(['brand' => 'NewBrand']))
            ->assertCreated();

        $this->assertDatabaseHas('brands', ['name' => 'NewBrand']);
    }

    public function test_reuses_existing_brand(): void
    {
        Brand::factory()->create(['name' => 'Apple']);

        $this->postJson('/api/products', $this->validPayload(['brand' => 'Apple']))
            ->assertCreated();

        $this->assertDatabaseCount('brands', 1);
    }

    public function test_creates_new_category_when_not_exists(): void
    {
        $this->postJson('/api/products', $this->validPayload(['category' => 'tablets']))
            ->assertCreated();

        $this->assertDatabaseHas('categories', ['name' => 'tablets']);
    }

    public function test_reuses_existing_category(): void
    {
        Category::factory()->create(['name' => 'smartphones']);

        $this->postJson('/api/products', $this->validPayload(['category' => 'smartphones']))
            ->assertCreated();

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_creates_and_attaches_tags(): void
    {
        $payload = $this->validPayload(['tags' => ['apple', 'flagship']]);

        $this->postJson('/api/products', $payload)->assertCreated();

        $this->assertDatabaseHas('tags', ['name' => 'apple']);
        $this->assertDatabaseHas('tags', ['name' => 'flagship']);

        $product = Product::where('sku', 'SAM-S25-001')->first();
        $this->assertCount(2, $product->tags);
    }

    public function test_does_not_duplicate_existing_tags(): void
    {
        Tag::factory()->create(['name' => 'apple']);

        $this->postJson('/api/products', $this->validPayload(['tags' => ['apple']]))
            ->assertCreated();

        $this->assertDatabaseCount('tags', 1);
    }

    public function test_creates_images(): void
    {
        $payload = $this->validPayload([
            'images' => ['https://example.com/a.jpg', 'https://example.com/b.jpg'],
        ]);

        $this->postJson('/api/products', $payload)->assertCreated();

        $product = Product::where('sku', 'SAM-S25-001')->first();
        $this->assertCount(2, $product->images);
        $this->assertDatabaseHas('images', ['url' => 'https://example.com/a.jpg']);
    }

    public function test_creates_reviews(): void
    {
        $payload = $this->validPayload([
            'reviews' => [[
                'rating'        => 5,
                'comment'       => 'Great phone!',
                'date'          => '2025-01-01T00:00:00.000Z',
                'reviewerName'  => 'John',
                'reviewerEmail' => 'john@example.com',
            ]],
        ]);

        $this->postJson('/api/products', $payload)->assertCreated();

        $product = Product::where('sku', 'SAM-S25-001')->first();
        $this->assertCount(1, $product->reviews);
        $this->assertDatabaseHas('reviews', ['reviewer_email' => 'john@example.com']);
    }

    public function test_stores_dimensions_as_flat_columns(): void
    {
        $payload = $this->validPayload([
            'dimensions' => ['width' => 7.5, 'height' => 16.2, 'depth' => 0.8],
        ]);

        $this->postJson('/api/products', $payload)->assertCreated();

        $this->assertDatabaseHas('products', [
            'sku'    => 'SAM-S25-001',
            'width'  => 7.5,
            'height' => 16.2,
            'depth'  => 0.8,
        ]);
    }

    public function test_stores_meta_fields(): void
    {
        $payload = $this->validPayload([
            'meta' => ['barcode' => '1234567890', 'qrCode' => 'https://example.com/qr.png'],
        ]);

        $this->postJson('/api/products', $payload)->assertCreated();

        $this->assertDatabaseHas('products', [
            'barcode' => '1234567890',
            'qr_code' => 'https://example.com/qr.png',
        ]);
    }

    public function test_local_product_has_null_external_id(): void
    {
        $this->postJson('/api/products', $this->validPayload())
            ->assertCreated();

        $this->assertDatabaseHas('products', [
            'sku'         => 'SAM-S25-001',
            'external_id' => null,
        ]);
    }

    public function test_title_is_required(): void
    {
        $this->postJson('/api/products', $this->validPayload(['title' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_price_is_required(): void
    {
        $this->postJson('/api/products', $this->validPayload(['price' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    public function test_brand_is_required(): void
    {
        $payload = $this->validPayload();
        unset($payload['brand']);

        $this->postJson('/api/products', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['brand']);
    }

    public function test_category_is_required(): void
    {
        $payload = $this->validPayload();
        unset($payload['category']);

        $this->postJson('/api/products', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category']);
    }

    public function test_sku_is_required(): void
    {
        $payload = $this->validPayload();
        unset($payload['sku']);

        $this->postJson('/api/products', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_sku_must_be_unique(): void
    {
        Product::factory()->create(['sku' => 'SAM-S25-001']);

        $this->postJson('/api/products', $this->validPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_price_cannot_be_negative(): void
    {
        $this->postJson('/api/products', $this->validPayload(['price' => -1]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    public function test_stock_cannot_be_negative(): void
    {
        $this->postJson('/api/products', $this->validPayload(['stock' => -1]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['stock']);
    }

    public function test_rating_must_be_between_0_and_5(): void
    {
        $this->postJson('/api/products', $this->validPayload(['rating' => 6]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_discount_percentage_must_be_between_0_and_100(): void
    {
        $this->postJson('/api/products', $this->validPayload(['discountPercentage' => 150]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['discountPercentage']);
    }

    public function test_images_must_be_valid_urls(): void
    {
        $this->postJson('/api/products', $this->validPayload(['images' => ['not-a-url']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['images.0']);
    }

    public function test_availability_status_must_be_valid_enum_value(): void
    {
        $this->postJson('/api/products', $this->validPayload(['availabilityStatus' => 'Invented Status']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['availabilityStatus']);
    }

    public function test_reviewer_email_must_be_valid(): void
    {
        $payload = $this->validPayload([
            'reviews' => [[
                'rating'        => 5,
                'comment'       => 'Great!',
                'reviewerEmail' => 'not-an-email',
            ]],
        ]);

        $this->postJson('/api/products', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reviews.0.reviewerEmail']);
    }
}
