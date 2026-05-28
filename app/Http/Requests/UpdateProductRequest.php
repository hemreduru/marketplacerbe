<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
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
            'sku' => 'sometimes|required|string|max:255',
            'name' => 'sometimes|required|string|max:500',
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'stock_quantity' => 'nullable|integer|min:0',
            'base_price' => 'sometimes|required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'currency' => 'nullable|string|size:3',
            'weight' => 'nullable|numeric|min:0',
            'dimensional_weight' => 'nullable|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => 'string|url',
            'attributes' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ];
    }
}
