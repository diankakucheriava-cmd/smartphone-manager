<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'external_id',
        'title',
        'description',
        'category_id',
        'price',
        'discount_percentage',
        'rating',
        'stock',
        'brand_id',
        'sku',
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
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function images()
    {
        return $this->hasMany(Image::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function scopeFilterByBrand(Builder $query, ?string $brandName): Builder
    {
        if (!$brandName) {
            return $query;
        }

        return $query->whereHas(
            'brand',
            fn(Builder $brandQuery) =>
            $brandQuery->where('name', $brandName)
        );
    }
}
