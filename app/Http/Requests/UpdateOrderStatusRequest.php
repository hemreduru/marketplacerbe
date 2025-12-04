<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'package_id' => 'required|integer',
            'status' => 'required|string|in:Created,Picking,Invoiced,Shipped,Cancelled,Delivered',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'package_id' => __('validation.attributes.package_id'),
            'status' => __('validation.attributes.status'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'package_id.required' => __('validation.required', ['attribute' => __('validation.attributes.package_id')]),
            'package_id.integer' => __('validation.integer', ['attribute' => __('validation.attributes.package_id')]),
            'status.required' => __('validation.required', ['attribute' => __('validation.attributes.status')]),
            'status.string' => __('validation.string', ['attribute' => __('validation.attributes.status')]),
            'status.in' => __('validation.in', ['attribute' => __('validation.attributes.status')]),
        ];
    }
}
