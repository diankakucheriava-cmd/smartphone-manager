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
}
