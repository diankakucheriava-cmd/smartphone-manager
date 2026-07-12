<?php

namespace App\Services;

use App\Enums\AvailabilityStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Validation\ProductImportValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ProductImportService
{
    private const API_URL = 'https://dummyjson.com/products/category/smartphones';

    public function __construct(private readonly ProductRelationService $relationService, private readonly ProductImportValidator $validator) {}

    public function importProductsFromApi(): void
    {
        $products = $this->fetchFromApi();

        foreach ($products as $data) {
            $validatedData = $this->validator::validate($data);

            DB::transaction(fn() => $this->importSingleProduct($validatedData));
        }
    }

    private function fetchFromApi(): array
    {
        $response = Http::timeout(15)
            ->retry(3, 500)
            ->get(self::API_URL, ['limit' => 0])
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
                'brand_id'    => $brand->id,
                'category_id' => $category->id,
            ]
        );

        $this->relationService->syncTags(
            $product,
            $data['tags']
        );

        $this->relationService->syncImages(
            $product,
            $data['images']
        );

        $this->relationService->syncReviews(
            $product,
            $data['reviews']
        );
    }

    private function mapProductData(array $data): array
    {
        return [
            'title' => $data['title'],
            'description' => $data['description'],
            'price' => $data['price'],
            'discount_percentage' => $data['discountPercentage'],
            'rating' => $data['rating'],
            'stock' => $data['stock'],
            'sku' => $data['sku'],
            'weight' => $data['weight'],

            'width' => $data['dimensions']['width'],
            'height' => $data['dimensions']['height'],
            'depth' => $data['dimensions']['depth'],

            'warranty_information' => $data['warrantyInformation'],
            'shipping_information' => $data['shippingInformation'],

            'availability_status' => AvailabilityStatus::from(
                $data['availabilityStatus']
            )->value,

            'return_policy' => $data['returnPolicy'],
            'minimum_order_quantity' => $data['minimumOrderQuantity'],

            'barcode' => $data['meta']['barcode'],
            'qr_code' => $data['meta']['qrCode'],
            'thumbnail' => $data['thumbnail'],
        ];
    }
}
