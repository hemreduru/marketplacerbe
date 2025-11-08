<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCredentialRequest extends FormRequest
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
            'marketplace_id' => 'required|exists:marketplaces,id',
            'api_key' => 'required|string|max:255',
            'api_secret' => 'required|string|max:255',
            'additional_credentials' => 'nullable|array',
            'is_active' => 'nullable|boolean',
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
            'marketplace_id.required' => __('api.validation_error'),
            'marketplace_id.exists' => __('api.marketplace.not_found'),
            'api_key.required' => __('api.validation_error'),
            'api_secret.required' => __('api.validation_error'),
        ];
    }
}
