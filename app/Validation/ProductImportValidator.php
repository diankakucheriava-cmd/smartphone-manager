<?php

namespace App\Validation;

use App\Enums\AvailabilityStatus;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductImportValidator
{
    public static function validate(array $data): array
    {
        return Validator::validate($data, [
            'id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['present', 'nullable', 'string'],

            'category' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],

            'price' => ['required', 'numeric', 'min:0'],
            'discountPercentage' => [
                'present',
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'rating' => [
                'present',
                'nullable',
                'numeric',
                'min:0',
                'max:5',
            ],
            'stock' => ['required', 'integer', 'min:0'],

            'sku' => ['required', 'string', 'max:255'],
            'weight' => ['present', 'nullable', 'numeric', 'min:0'],

            'dimensions' => ['required', 'array'],
            'dimensions.width' => ['required', 'numeric', 'min:0'],
            'dimensions.height' => ['required', 'numeric', 'min:0'],
            'dimensions.depth' => ['required', 'numeric', 'min:0'],

            'warrantyInformation' => [
                'present',
                'nullable',
                'string',
            ],
            'shippingInformation' => [
                'present',
                'nullable',
                'string',
            ],

            'availabilityStatus' => [
                'required',
                Rule::enum(AvailabilityStatus::class),
            ],

            'returnPolicy' => [
                'present',
                'nullable',
                'string',
            ],
            'minimumOrderQuantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'meta' => ['required', 'array'],
            'meta.barcode' => [
                'present',
                'nullable',
                'string',
                'max:255',
            ],
            'meta.qrCode' => [
                'present',
                'nullable',
                'url',
            ],

            'thumbnail' => [
                'present',
                'nullable',
                'url',
            ],

            'tags' => ['required', 'array'],
            'tags.*' => ['required', 'string', 'max:255'],

            'images' => ['required', 'array'],
            'images.*' => ['required', 'url'],

            'reviews' => ['required', 'array'],
            'reviews.*.rating' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],
            'reviews.*.comment' => [
                'required',
                'string',
            ],
            'reviews.*.date' => [
                'required',
                'date',
            ],
            'reviews.*.reviewerName' => [
                'required',
                'string',
                'max:255',
            ],
            'reviews.*.reviewerEmail' => [
                'required',
                'email',
                'max:255',
            ],
        ]);
    }
}
