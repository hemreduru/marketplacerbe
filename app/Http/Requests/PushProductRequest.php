<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PushProductRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'marketplace_id' => 'required|exists:marketplaces,id',
            'force' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'product_id.required' => __('api.validation_error'),
            'product_id.exists' => __('api.product.not_found'),
            'marketplace_id.required' => __('api.validation_error'),
            'marketplace_id.exists' => __('api.marketplace.not_found'),
        ];
    }
}
