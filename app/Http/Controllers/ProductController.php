<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;

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

    public function show(int $id)
    {
        $product = Product::with(['brand', 'category', 'tags', 'images', 'reviews'])->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return new ProductResource($product);
    }
}
