<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(private readonly ProductRelationService $relationService) {}

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $brand    = Brand::firstOrCreate(['name' => $data['brand']]);
            $category = Category::firstOrCreate(['name' => $data['category']]);

            $product = Product::create([
                'title'                  => $data['title'],
                'description'            => $data['description'] ?? null,
                'category_id'            => $category->id,
                'price'                  => $data['price'],
                'discount_percentage'    => $data['discountPercentage'] ?? null,
                'rating'                 => $data['rating'] ?? null,
                'stock'                  => $data['stock'] ?? 0,
                'brand_id'               => $brand->id,
                'sku'                    => $data['sku'],
                'weight'                 => $data['weight'] ?? null,
                'width'                  => $data['dimensions']['width'] ?? null,
                'height'                 => $data['dimensions']['height'] ?? null,
                'depth'                  => $data['dimensions']['depth'] ?? null,
                'warranty_information'   => $data['warrantyInformation'] ?? null,
                'shipping_information'   => $data['shippingInformation'] ?? null,
                'availability_status'    => $data['availabilityStatus'] ?? null,
                'return_policy'          => $data['returnPolicy'] ?? null,
                'minimum_order_quantity' => $data['minimumOrderQuantity'] ?? 1,
                'barcode'                => $data['meta']['barcode'] ?? null,
                'qr_code'                => $data['meta']['qrCode'] ?? null,
                'thumbnail'              => $data['thumbnail'] ?? null,
            ]);

            $this->relationService->syncTags($product, $data['tags'] ?? []);
            $this->relationService->syncImages($product, $data['images'] ?? []);
            $this->relationService->syncReviews($product, $data['reviews'] ?? []);

            return $product->load([
                'brand',
                'category',
                'tags',
                'images',
                'reviews',
            ]);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $fields = [];

            $map = [
                'title'               => 'title',
                'description'         => 'description',
                'price'               => 'price',
                'discountPercentage'  => 'discount_percentage',
                'rating'              => 'rating',
                'stock'               => 'stock',
                'sku'                 => 'sku',
                'weight'              => 'weight',
                'warrantyInformation' => 'warranty_information',
                'shippingInformation' => 'shipping_information',
                'availabilityStatus'  => 'availability_status',
                'returnPolicy'        => 'return_policy',
                'minimumOrderQuantity' => 'minimum_order_quantity',
                'thumbnail'           => 'thumbnail',
            ];

            foreach ($map as $input => $column) {
                if (array_key_exists($input, $data)) {
                    $fields[$column] = $data[$input];
                }
            }

            if (array_key_exists('brand', $data)) {
                $fields['brand_id'] = Brand::firstOrCreate(['name' => $data['brand']])->id;
            }

            if (array_key_exists('category', $data)) {
                $fields['category_id'] = Category::firstOrCreate(['name' => $data['category']])->id;
            }

            if (array_key_exists('dimensions', $data)) {
                if (array_key_exists('width', $data['dimensions'])) {
                    $fields['width'] = $data['dimensions']['width'];
                }

                if (array_key_exists('height', $data['dimensions'])) {
                    $fields['height'] = $data['dimensions']['height'];
                }

                if (array_key_exists('depth', $data['dimensions'])) {
                    $fields['depth'] = $data['dimensions']['depth'];
                }
            }

            if (array_key_exists('meta', $data)) {
                if (array_key_exists('barcode', $data['meta'])) {
                    $fields['barcode'] = $data['meta']['barcode'];
                }

                if (array_key_exists('qrCode', $data['meta'])) {
                    $fields['qr_code'] = $data['meta']['qrCode'];
                }
            }

            if (!empty($fields)) {
                $product->update($fields);
            }

            if (array_key_exists('tags', $data)) {
                $this->relationService->syncTags($product, $data['tags']);
            }

            if (array_key_exists('images', $data)) {
                $this->relationService->syncImages($product, $data['images']);
            }

            if (array_key_exists('reviews', $data)) {
                $this->relationService->syncReviews($product, $data['reviews']);
            }

            return $product->load(['brand', 'category', 'tags', 'images', 'reviews']);
        });
    }
}
