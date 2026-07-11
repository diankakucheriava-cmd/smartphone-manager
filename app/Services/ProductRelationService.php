<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Tag;

class ProductRelationService
{
    public function syncTags(Product $product, array $tags): void
    {
        $tagIds = [];

        foreach ($tags as $name) {
            $tagIds[] = Tag::firstOrCreate(['name' => $name])->id;
        }

        $product->tags()->sync($tagIds);
    }

    public function syncImages(Product $product, array $images): void
    {
        $product->images()->delete();

        foreach ($images as $url) {
            $product->images()->create(['url' => $url]);
        }
    }

    public function syncReviews(Product $product, array $reviews): void
    {
        $product->reviews()->delete();

        foreach ($reviews as $review) {
            $product->reviews()->create([
                'rating'         => $review['rating'],
                'comment'        => $review['comment'],
                'reviewed_at'    => $review['date'] ?? null,
                'reviewer_name'  => $review['reviewerName'] ?? null,
                'reviewer_email' => $review['reviewerEmail'] ?? null,
            ]);
        }
    }
}
