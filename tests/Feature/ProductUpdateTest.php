<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\Review;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_only_title(): void
    {
        $product = Product::factory()->create(['title' => 'Old Title', 'price' => 100.00]);

        $this->patchJson("/api/products/{$product->id}", ['title' => 'New Title'])
            ->assertOk()
            ->assertJsonPath('title', 'New Title')
            ->assertJsonPath('price', 100);
    }

    public function test_does_not_change_absent_fields(): void
    {
        $product = Product::factory()->create([
            'title' => 'Original',
            'stock' => 50,
        ]);

        $this->patchJson("/api/products/{$product->id}", ['title' => 'Updated'])
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'id'    => $product->id,
            'title' => 'Updated',
            'stock' => 50,
        ]);
    }

    public function test_updates_price(): void
    {
        $product = Product::factory()->create(['price' => 100.00]);

        $this->patchJson("/api/products/{$product->id}", ['price' => 299.99])
            ->assertOk()
            ->assertJsonPath('price', 299.99);
    }

    public function test_updates_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $this->patchJson("/api/products/{$product->id}", ['stock' => 99])
            ->assertOk();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 99]);
    }

    public function test_updates_brand_and_creates_if_not_exists(): void
    {
        $product = Product::factory()->create();

        $this->patchJson("/api/products/{$product->id}", ['brand' => 'NewBrand'])
            ->assertOk()
            ->assertJsonPath('brand', 'NewBrand');

        $this->assertDatabaseHas('brands', ['name' => 'NewBrand']);
    }

    public function test_updates_brand_and_reuses_existing(): void
    {
        $brand   = Brand::factory()->create(['name' => 'Apple']);
        $product = Product::factory()->create();

        $this->patchJson("/api/products/{$product->id}", ['brand' => 'Apple'])
            ->assertOk();

        $this->assertSame(
            1,
            Brand::where('name', 'Apple')->count()
        );
    }

    public function test_updates_category_and_creates_if_not_exists(): void
    {
        $product = Product::factory()->create();

        $this->patchJson("/api/products/{$product->id}", [
            'category' => 'tablets',
        ])
            ->assertOk()
            ->assertJsonPath('category', 'tablets');

        $this->assertDatabaseHas('categories', [
            'name' => 'tablets',
        ]);
    }

    public function test_updates_category_and_reuses_existing(): void
    {
        Category::factory()->create([
            'name' => 'tablets',
        ]);

        $product = Product::factory()->create();

        $this->patchJson("/api/products/{$product->id}", [
            'category' => 'tablets',
        ])->assertOk();

        $this->assertSame(
            1,
            Category::where('name', 'tablets')->count()
        );
    }

    public function test_updates_tags_when_present(): void
    {
        $product = Product::factory()
            ->has(Tag::factory()->count(2), 'tags')
            ->create();

        $this->patchJson("/api/products/{$product->id}", ['tags' => ['newtag']])
            ->assertOk();

        $this->assertCount(1, $product->fresh()->tags);
        $this->assertDatabaseHas('tags', ['name' => 'newtag']);
    }

    public function test_leaves_tags_unchanged_when_absent(): void
    {
        $product = Product::factory()
            ->has(Tag::factory()->count(2), 'tags')
            ->create();

        $this->patchJson("/api/products/{$product->id}", ['title' => 'Updated'])
            ->assertOk();

        $this->assertCount(2, $product->fresh()->tags);
    }

    public function test_removes_all_tags_when_empty_array_sent(): void
    {
        $product = Product::factory()
            ->has(Tag::factory()->count(2), 'tags')
            ->create();

        $this->patchJson("/api/products/{$product->id}", ['tags' => []])
            ->assertOk();

        $this->assertCount(0, $product->fresh()->tags);
    }

    public function test_updates_reviews_when_present(): void
    {
        $product = Product::factory()
            ->has(Review::factory()->count(2), 'reviews')
            ->create();

        $this->patchJson("/api/products/{$product->id}", [
            'reviews' => [[
                'rating' => 5,
                'comment' => 'Updated review',
                'date' => '2026-01-01T00:00:00Z',
                'reviewerName' => 'John',
                'reviewerEmail' => 'john@example.com',
            ]],
        ])->assertOk();

        $this->assertCount(1, $product->fresh()->reviews);
        $this->assertDatabaseHas('reviews', [
            'comment' => 'Updated review',
        ]);
    }

    public function test_leaves_reviews_unchanged_when_absent(): void
    {
        $product = Product::factory()
            ->has(Review::factory()->count(2), 'reviews')
            ->create();

        $this->patchJson("/api/products/{$product->id}", [
            'title' => 'Updated',
        ])->assertOk();

        $this->assertCount(2, $product->fresh()->reviews);
    }

    public function test_removes_all_reviews_when_empty_array_sent(): void
    {
        $product = Product::factory()
            ->has(Review::factory()->count(2), 'reviews')
            ->create();

        $this->patchJson("/api/products/{$product->id}", [
            'reviews' => [],
        ])->assertOk();

        $this->assertCount(0, $product->fresh()->reviews);
    }

    public function test_updates_images_when_present(): void
    {
        $product = Product::factory()
            ->has(Image::factory()->count(2), 'images')
            ->create();

        $this->patchJson("/api/products/{$product->id}", [
            'images' => ['https://example.com/new.jpg'],
        ])->assertOk();

        $this->assertCount(1, $product->fresh()->images);
        $this->assertDatabaseHas('images', ['url' => 'https://example.com/new.jpg']);
    }

    public function test_leaves_images_unchanged_when_absent(): void
    {
        $product = Product::factory()
            ->has(Image::factory()->count(2), 'images')
            ->create();

        $this->patchJson("/api/products/{$product->id}", ['title' => 'Updated'])
            ->assertOk();

        $this->assertCount(2, $product->fresh()->images);
    }

    public function test_removes_all_images_when_empty_array_sent(): void
    {
        $product = Product::factory()
            ->has(Image::factory()->count(2), 'images')
            ->create();

        $this->patchJson("/api/products/{$product->id}", ['images' => []])
            ->assertOk();

        $this->assertCount(0, $product->fresh()->images);
    }

    public function test_updates_only_sent_dimension(): void
    {
        $product = Product::factory()->create([
            'width' => 5.0,
            'height' => 15.0,
            'depth' => 1.0,
        ]);

        $this->patchJson("/api/products/{$product->id}", [
            'dimensions' => [
                'width' => 7.5,
            ],
        ])->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'width' => 7.5,
            'height' => 15.0,
            'depth' => 1.0,
        ]);
    }

    public function test_allows_same_sku_when_updating_same_product(): void
    {
        $product = Product::factory()->create(['sku' => 'SKU-001']);

        $this->patchJson("/api/products/{$product->id}", ['sku' => 'SKU-001'])
            ->assertOk();
    }

    public function test_returns_404_for_unknown_product(): void
    {
        $this->patchJson('/api/products/99999', ['title' => 'X'])
            ->assertNotFound();
    }

    public function test_rejects_sku_belonging_to_another_product(): void
    {
        Product::factory()->create(['sku' => 'TAKEN-SKU']);
        $product = Product::factory()->create(['sku' => 'MY-SKU']);

        $this->patchJson("/api/products/{$product->id}", ['sku' => 'TAKEN-SKU'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_rejects_negative_price(): void
    {
        $product = Product::factory()->create();

        $this->patchJson("/api/products/{$product->id}", ['price' => -5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    public function test_rejects_invalid_availability_status(): void
    {
        $product = Product::factory()->create();

        $this->patchJson("/api/products/{$product->id}", ['availabilityStatus' => 'BadStatus'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['availabilityStatus']);
    }
}
