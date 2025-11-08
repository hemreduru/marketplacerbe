<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkPushProductRequest extends FormRequest
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
            'marketplace_credential_id' => 'required|exists:user_marketplace_credentials,id',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'required|integer|exists:products,id',
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
            'marketplace_credential_id.required' => __('api.validation.credential_id_required'),
            'marketplace_credential_id.exists' => __('api.validation.credential_not_found'),
            'product_ids.required' => __('api.validation.product_ids_required'),
            'product_ids.array' => __('api.validation.product_ids_array'),
            'product_ids.min' => __('api.validation.product_ids_min'),
            'product_ids.*.required' => __('api.validation.product_id_required'),
            'product_ids.*.integer' => __('api.validation.product_id_integer'),
            'product_ids.*.exists' => __('api.validation.product_not_found'),
        ];
    }
}
