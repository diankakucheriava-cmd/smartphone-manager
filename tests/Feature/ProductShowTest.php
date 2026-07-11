<?php

namespace Tests\Feature;

use App\Models\Image;
use App\Models\Product;
use App\Models\Review;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function fakeNbu(float $usd = 41.5, float $eur = 45.0): void
    {
        Http::fake([
            'bank.gov.ua/*' => Http::response([
                ['cc' => 'USD', 'rate' => $usd],
                ['cc' => 'EUR', 'rate' => $eur],
            ]),
        ]);
    }

    public function test_returns_single_product(): void
    {
        $product = Product::factory()->create(['title' => 'iPhone 15']);

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('id', $product->id)
            ->assertJsonPath('title', 'iPhone 15');
    }

    public function test_returns_404_for_unknown_product(): void
    {
        $this->getJson('/api/products/99999')
            ->assertNotFound();
    }

    public function test_loads_brand_category_tags_images_and_reviews(): void
    {
        $product = Product::factory()
            ->has(Image::factory()->count(2),  'images')
            ->has(Review::factory()->count(1), 'reviews')
            ->has(Tag::factory()->count(2),    'tags')
            ->create();

        $response = $this->getJson("/api/products/{$product->id}")->assertOk();

        $this->assertCount(2, $response->json('images'));
        $this->assertCount(1, $response->json('reviews'));
        $this->assertCount(2, $response->json('tags'));
        $this->assertNotNull($response->json('brand'));
        $this->assertNotNull($response->json('category'));
    }

    public function test_defaults_currency_to_usd(): void
    {
        $product = Product::factory()->create(['price' => 500.50]);

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('price', 500.50)
            ->assertJsonPath('currency', 'USD');
    }

    public function test_currency_usd_keeps_price(): void
    {
        $product = Product::factory()->create(['price' => 299.99]);

        $this->getJson("/api/products/{$product->id}?currency=USD")
            ->assertOk()
            ->assertJsonPath('price', 299.99);
    }

    public function test_currency_uah_converts_price(): void
    {
        $this->fakeNbu(usd: 41.5, eur: 45.0);

        $product = Product::factory()->create(['price' => 100.50]);

        $this->getJson("/api/products/{$product->id}?currency=UAH")
            ->assertOk()
            ->assertJsonPath('price', round(100.50 * 41.5, 2))
            ->assertJsonPath('currency', 'UAH');
    }

    public function test_currency_eur_converts_price_via_cross_rate(): void
    {
        $this->fakeNbu(usd: 41.5, eur: 45.0);

        $product = Product::factory()->create(['price' => 100.50]);

        $expected = round(100.50 * 41.5 / 45.0, 2);

        $this->getJson("/api/products/{$product->id}?currency=EUR")
            ->assertOk()
            ->assertJsonPath('price', $expected)
            ->assertJsonPath('currency', 'EUR');
    }

    public function test_invalid_currency_returns_422(): void
    {
        $product = Product::factory()->create();

        $this->getJson("/api/products/{$product->id}?currency=GBP")
            ->assertUnprocessable();
    }
}
