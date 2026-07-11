<?php

namespace App\Http\Resources;

use App\Enums\Currency;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currency = Currency::tryFrom($request->query('currency', '')) ?? Currency::USD;
        $price    = $currency === Currency::USD
            ? $this->price
            : app(CurrencyService::class)->convert((float) $this->price, $currency);

        return [
            'id'                     => $this->id,
            'title'                  => $this->title,
            'description'            => $this->description,
            'price'                  => $price,
            'currency'               => $currency->value,
            'discount_percentage'    => $this->discount_percentage,
            'rating'                 => $this->rating,
            'stock'                  => $this->stock,
            'sku'                    => $this->sku,

            'brand'                  => $this->brand?->name,
            'category'               => $this->category?->name,

            'weight'                 => $this->weight,
            'width'                  => $this->width,
            'height'                 => $this->height,
            'depth'                  => $this->depth,

            'warranty_information'   => $this->warranty_information,
            'shipping_information'   => $this->shipping_information,
            'availability_status'    => $this->availability_status,
            'return_policy'          => $this->return_policy,
            'minimum_order_quantity' => $this->minimum_order_quantity,

            'barcode'                => $this->barcode,
            'qr_code'                => $this->qr_code,
            'thumbnail'              => $this->thumbnail,

            'images'  => $this->images->pluck('url'),
            'tags'    => $this->tags->pluck('name'),
            'reviews' => $this->reviews->map(fn($review) => [
                'id'             => $review->id,
                'rating'         => $review->rating,
                'comment'        => $review->comment,
                'reviewer_name'  => $review->reviewer_name,
                'reviewer_email' => $review->reviewer_email,
                'reviewed_at'    => $review->reviewed_at,
            ]),
        ];
    }
}
