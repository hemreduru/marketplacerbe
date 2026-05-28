<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PullProductRequest extends FormRequest
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
            'marketplace_id' => 'required|exists:marketplaces,id',
            'page' => 'nullable|integer|min:0',
            'size' => 'nullable|integer|min:1|max:200',
            'approved' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'marketplace_id.required' => __('api.validation_error'),
            'marketplace_id.exists' => __('api.marketplace.not_found'),
        ];
    }
}
