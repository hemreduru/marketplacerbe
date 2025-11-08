<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkSyncRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Will be handled by policies in Phase 12
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'marketplace_product_ids' => 'required|array|min:1',
            'marketplace_product_ids.*' => 'required|integer|exists:marketplace_products,id',
            'sync_type' => 'required|in:stock,price,both',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'marketplace_product_ids.required' => __('api.validation.marketplace_product_ids_required'),
            'marketplace_product_ids.array' => __('api.validation.marketplace_product_ids_array'),
            'marketplace_product_ids.min' => __('api.validation.marketplace_product_ids_min'),
            'marketplace_product_ids.*.required' => __('api.validation.marketplace_product_id_required'),
            'marketplace_product_ids.*.integer' => __('api.validation.marketplace_product_id_integer'),
            'marketplace_product_ids.*.exists' => __('api.validation.marketplace_product_not_found'),
            'sync_type.required' => __('api.validation.sync_type_required'),
            'sync_type.in' => __('api.validation.sync_type_invalid'),
        ];
    }
}
