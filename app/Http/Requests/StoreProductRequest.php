<?php

namespace App\Http\Requests;

use App\Enums\AvailabilityStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'discountPercentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'stock' => ['required', 'integer', 'min:0'],
            'brand' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'weight' => ['nullable', 'numeric', 'min:0'],

            'dimensions' => ['nullable', 'array'],
            'dimensions.width' => ['nullable', 'numeric', 'min:0'],
            'dimensions.height' => ['nullable', 'numeric', 'min:0'],
            'dimensions.depth' => ['nullable', 'numeric', 'min:0'],

            'warrantyInformation' => ['nullable', 'string'],
            'shippingInformation' => ['nullable', 'string'],
            'availabilityStatus' => ['nullable', Rule::enum(AvailabilityStatus::class)],
            'returnPolicy' => ['nullable', 'string'],
            'minimumOrderQuantity' => ['nullable', 'integer', 'min:1'],

            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:255'],

            'images' => ['nullable', 'array'],
            'images.*' => ['url'],

            'reviews' => ['nullable', 'array'],
            'reviews.*.rating' => ['required', 'integer', 'min:1', 'max:5'],
            'reviews.*.comment' => ['required', 'string'],
            'reviews.*.date' => ['nullable', 'date'],
            'reviews.*.reviewerName' => ['required', 'string', 'max:255'],
            'reviews.*.reviewerEmail' => ['required', 'email', 'max:255'],

            'meta' => ['nullable', 'array'],
            'meta.barcode' => ['nullable', 'string', 'max:255'],
            'meta.qrCode' => ['nullable', 'url'],

            'thumbnail' => ['nullable', 'url'],
        ];
    }
}
