<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

use App\Http\Requests\IndexProductRequest;
use App\Http\Requests\ShowProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;

class ProductController extends Controller
{
    public function index(IndexProductRequest $request)
    {
        $limit = (int) $request->input('limit', 15);

        $query = Product::query()
            ->with(['brand', 'category', 'tags', 'images', 'reviews'])
            ->filterByBrand($request->input('brand'));

        return ProductResource::collection(
            $query
                ->paginate($limit)
                ->withQueryString()
        );
    }

    public function show(ShowProductRequest $request, Product $product): JsonResponse
    {
        $product->load([
            'brand',
            'category',
            'tags',
            'images',
            'reviews',
        ]);

        return response()->json(new ProductResource($product), 200);
    }

    public function store(StoreProductRequest $request, ProductService $productService): JsonResponse
    {
        $product = $productService->create($request->validated());

        return response()->json(new ProductResource($product), 201);
    }

    public function update(UpdateProductRequest $request, ProductService $productService,  Product $product): JsonResponse
    {
        $product = $productService->update($product, $request->validated());

        return response()->json(new ProductResource($product), 200);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }
}
