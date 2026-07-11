<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use App\Enums\AvailabilityStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'category' => ['sometimes', 'string', 'max:255'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'discountPercentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'rating' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:5'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'brand' => ['sometimes', 'string', 'max:255'],

            'sku' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($this->route('id')),
            ],

            'weight' => ['sometimes', 'nullable', 'numeric', 'min:0'],

            'dimensions' => ['sometimes', 'array'],
            'dimensions.width' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'dimensions.height' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'dimensions.depth' => ['sometimes', 'nullable', 'numeric', 'min:0'],

            'warrantyInformation' => ['sometimes', 'nullable', 'string'],
            'shippingInformation' => ['sometimes', 'nullable', 'string'],
            'availabilityStatus' => ['sometimes', 'nullable', Rule::enum(AvailabilityStatus::class)],
            'returnPolicy' => ['sometimes', 'nullable', 'string'],
            'minimumOrderQuantity' => ['sometimes', 'nullable', 'integer', 'min:1'],

            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:255'],

            'images' => ['sometimes', 'array'],
            'images.*' => ['url'],

            'reviews' => ['sometimes', 'array'],
            'reviews.*.rating' => ['required_with:reviews', 'integer', 'min:1', 'max:5'],
            'reviews.*.comment' => ['required_with:reviews', 'string'],
            'reviews.*.date' => ['nullable', 'date'],
            'reviews.*.reviewerName' => ['nullable', 'string', 'max:255'],
            'reviews.*.reviewerEmail' => ['nullable', 'email', 'max:255'],

            'meta' => ['sometimes', 'array'],
            'meta.barcode' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta.qrCode' => ['sometimes', 'nullable', 'url'],

            'thumbnail' => ['sometimes', 'nullable', 'url'],
        ];
    }
}
