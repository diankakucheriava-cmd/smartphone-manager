<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductIndexTest extends TestCase
{
    use RefreshDatabase;

    private const NBU_URL = 'bank.gov.ua/*';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function fakeNbu(float $usd = 41.5, float $eur = 45.0): void
    {
        Http::fake([
            self::NBU_URL => Http::response([
                ['cc' => 'USD', 'rate' => $usd],
                ['cc' => 'EUR', 'rate' => $eur],
            ]),
        ]);
    }

    public function test_returns_paginated_list_of_products(): void
    {
        Product::factory()->count(5)->create();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'per_page', 'total'],
                'links',
            ]);
    }

    public function test_default_limit_is_15(): void
    {
        Product::factory()->count(20)->create();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonCount(15, 'data');
    }

    public function test_custom_limit_is_applied(): void
    {
        Product::factory()->count(10)->create();

        $this->getJson('/api/products?limit=3')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_page_parameter_works(): void
    {
        Product::factory()->count(5)->create();

        $response = $this->getJson('/api/products?limit=2&page=2');

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_brand_filter_returns_only_matching_products(): void
    {
        $apple    = Brand::factory()->create(['name' => 'Apple']);
        $samsung  = Brand::factory()->create(['name' => 'Samsung']);
        $category = Category::factory()->create();

        Product::factory()->count(2)->create(['brand_id' => $apple->id,   'category_id' => $category->id]);
        Product::factory()->count(3)->create(['brand_id' => $samsung->id, 'category_id' => $category->id]);

        $this->getJson('/api/products?brand=Apple')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.brand', 'Apple');
    }

    public function test_unknown_brand_returns_empty_collection(): void
    {
        Product::factory()->count(3)->create();

        $this->getJson('/api/products?brand=UnknownBrand')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_currency_defaults_to_usd(): void
    {
        Product::factory()->create(['price' => 100.50]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.currency', 'USD')
            ->assertJsonPath('data.0.price', 100.50);
    }

    public function test_currency_usd_keeps_original_price(): void
    {
        Product::factory()->create(['price' => 199.99]);

        $this->getJson('/api/products?currency=USD')
            ->assertOk()
            ->assertJsonPath('data.0.price', 199.99)
            ->assertJsonPath('data.0.currency', 'USD');
    }

    public function test_currency_uah_converts_price(): void
    {
        $this->fakeNbu(usd: 41.5, eur: 45.0);

        Product::factory()->create(['price' => 100.50]);

        $this->getJson('/api/products?currency=UAH')
            ->assertOk()
            ->assertJsonPath('data.0.price', round(100.50 * 41.5, 2))
            ->assertJsonPath('data.0.currency', 'UAH');
    }

    public function test_currency_eur_converts_price_via_uah_cross_rate(): void
    {
        $this->fakeNbu(usd: 41.5, eur: 45.0);

        Product::factory()->create(['price' => 100.50]);

        $expected = round(100.50 * 41.5 / 45.0, 2);

        $this->getJson('/api/products?currency=EUR')
            ->assertOk()
            ->assertJsonPath('data.0.price', $expected)
            ->assertJsonPath('data.0.currency', 'EUR');
    }

    public function test_response_has_product_resource_structure(): void
    {
        Product::factory()
            ->has(\App\Models\Image::factory()->count(1),   'images')
            ->has(\App\Models\Review::factory()->count(1),  'reviews')
            ->has(\App\Models\Tag::factory()->count(1),     'tags')
            ->create();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'title',
                    'description',
                    'price',
                    'currency',
                    'discount_percentage',
                    'rating',
                    'stock',
                    'sku',
                    'brand',
                    'category',
                    'weight',
                    'width',
                    'height',
                    'depth',
                    'warranty_information',
                    'shipping_information',
                    'availability_status',
                    'return_policy',
                    'minimum_order_quantity',
                    'barcode',
                    'qr_code',
                    'thumbnail',
                    'images',
                    'tags',
                    'reviews',
                ]],
            ]);
    }

    public function test_response_does_not_expose_internal_ids(): void
    {
        Product::factory()->create();

        $response = $this->getJson('/api/products')->assertOk();

        $product = $response->json('data.0');
        $this->assertArrayNotHasKey('brand_id',    $product);
        $this->assertArrayNotHasKey('category_id', $product);
        $this->assertArrayNotHasKey('pivot',       $product);
    }

    public function test_invalid_currency_returns_422(): void
    {
        $this->getJson('/api/products?currency=GBP')
            ->assertUnprocessable();
    }

    public function test_invalid_limit_returns_422(): void
    {
        $this->getJson('/api/products?limit=abc')
            ->assertUnprocessable();
    }

    public function test_limit_above_maximum_returns_422(): void
    {
        $this->getJson('/api/products?limit=200')
            ->assertUnprocessable();
    }

    public function test_invalid_page_returns_422(): void
    {
        $this->getJson('/api/products?page=0')
            ->assertUnprocessable();
    }
}
