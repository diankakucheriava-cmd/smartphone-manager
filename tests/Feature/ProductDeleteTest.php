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

class ProductDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_existing_product(): void
    {
        $product = Product::factory()->create();

        $this->deleteJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Product deleted successfully.');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_returns_404_for_unknown_product(): void
    {
        $this->deleteJson('/api/products/99999')
            ->assertNotFound();
    }

    public function test_cascade_deletes_images(): void
    {
        $product = Product::factory()
            ->has(Image::factory()->count(2), 'images')
            ->create();

        $this->deleteJson("/api/products/{$product->id}")->assertOk();

        $this->assertDatabaseMissing('images', ['product_id' => $product->id]);
    }

    public function test_cascade_deletes_reviews(): void
    {
        $product = Product::factory()
            ->has(Review::factory()->count(2), 'reviews')
            ->create();

        $this->deleteJson("/api/products/{$product->id}")->assertOk();

        $this->assertDatabaseMissing('reviews', ['product_id' => $product->id]);
    }

    public function test_removes_product_tag_pivot_rows(): void
    {
        $product = Product::factory()
            ->has(Tag::factory()->count(2), 'tags')
            ->create();

        $this->deleteJson("/api/products/{$product->id}")->assertOk();

        $this->assertDatabaseMissing('product_tag', ['product_id' => $product->id]);
    }

    public function test_does_not_delete_shared_tags(): void
    {
        $tag      = Tag::factory()->create(['name' => 'shared']);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $product1->tags()->attach($tag);
        $product2->tags()->attach($tag);

        $this->deleteJson("/api/products/{$product1->id}")->assertOk();

        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
        $this->assertCount(1, $product2->fresh()->tags);
    }

    public function test_does_not_delete_brand(): void
    {
        $brand   = Brand::factory()->create();
        $product = Product::factory()->create(['brand_id' => $brand->id]);

        $this->deleteJson("/api/products/{$product->id}")->assertOk();

        $this->assertDatabaseHas('brands', ['id' => $brand->id]);
    }

    public function test_does_not_delete_category(): void
    {
        $category = Category::factory()->create();
        $product  = Product::factory()->create(['category_id' => $category->id]);

        $this->deleteJson("/api/products/{$product->id}")->assertOk();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
