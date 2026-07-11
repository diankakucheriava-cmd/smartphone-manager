<?php

namespace App\Services;

use App\Enums\AvailabilityStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ProductImportService
{
    private const API_URL = 'https://dummyjson.com/products/category/smartphones';

    public function importProductsFromApi(): void
    {
        $products = $this->fetchFromApi();

        foreach ($products as $data) {
            DB::transaction(fn() => $this->importSingleProduct($data));
        }
    }

    private function fetchFromApi(): array
    {
        $response = Http::timeout(15)
            ->retry(3, 500)
            ->get(self::API_URL, [
                'limit' => 0,
            ])
            ->throw();

        $products = $response->json('products');

        if (!is_array($products)) {
            throw new \UnexpectedValueException(
                'Invalid products data received from DummyJSON API'
            );
        }

        return $products;
    }

    private function importSingleProduct(array $data): void
    {
        $brand    = Brand::firstOrCreate(['name' => $data['brand']]);
        $category = Category::firstOrCreate(['name' => $data['category']]);

        $product = Product::updateOrCreate(
            ['external_id' => $data['id']],
            [
                ...$this->mapProductData($data),
                'brand_id' => $brand->id,
                'category_id' => $category->id,
            ]
        );

        $this->syncTags($product, $data['tags'] ?? []);
        $this->syncImages($product, $data['images'] ?? []);
        $this->syncReviews($product, $data['reviews'] ?? []);
    }

    private function syncTags(Product $product, array $tags): void
    {
        $tagIds = [];

        foreach ($tags as $tagName) {
            $tag = Tag::firstOrCreate([
                'name' => $tagName,
            ]);

            $tagIds[] = $tag->id;
        }

        $product->tags()->sync($tagIds);
    }

    private function syncImages(Product $product, array $images): void
    {
        $product->images()->delete();

        $product->images()->createMany(
            array_map(fn(string $url) => ['url' => $url], $images)
        );
    }

    private function syncReviews(Product $product, array $reviews): void
    {
        $product->reviews()->delete();

        foreach ($reviews as $review) {
            $product->reviews()->create([
                'rating' => $review['rating'],
                'comment' => $review['comment'],
                'reviewed_at' => $review['date'],
                'reviewer_name' => $review['reviewerName'],
                'reviewer_email' => $review['reviewerEmail'],
            ]);
        }
    }

    private function mapProductData(array $data): array
    {
        return [
            'title'                => $data['title'] ?? null,
            'description'          => $data['description'] ?? null,
            'price'                => $data['price'] ?? null,
            'discount_percentage'  => $data['discountPercentage'] ?? null,
            'rating'               => $data['rating'] ?? null,
            'stock'                => $data['stock'] ?? null,
            'sku'                  => $data['sku'] ?? null,
            'weight'               => $data['weight'] ?? null,
            'width'                => $data['dimensions']['width'] ?? null,
            'height'               => $data['dimensions']['height'] ?? null,
            'depth'                => $data['dimensions']['depth'] ?? null,
            'warranty_information' => $data['warrantyInformation'] ?? null,
            'shipping_information' => $data['shippingInformation'] ?? null,
            'availability_status'  => (AvailabilityStatus::tryFrom($data['availabilityStatus'] ?? '')
                ?? AvailabilityStatus::OutOfStock)->value,
            'return_policy'        => $data['returnPolicy'] ?? null,
            'minimum_order_quantity' => $data['minimumOrderQuantity'] ?? null,
            'barcode'              => $data['meta']['barcode'] ?? null,
            'qr_code'              => $data['meta']['qrCode'] ?? null,
            'thumbnail'            => $data['thumbnail'] ?? null,
        ];
    }
}
