<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMarketplaceSettingsRequest extends FormRequest
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
            'api_key' => 'required|string|max:255',
            'api_secret' => 'required|string|max:255',
            'additional_credentials' => 'nullable|array',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'marketplace_id' => __('settings.marketplace'),
            'api_key' => __('common.api_key'),
            'api_secret' => __('common.api_secret'),
        ];
    }
}
