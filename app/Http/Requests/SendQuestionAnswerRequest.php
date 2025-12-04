<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendQuestionAnswerRequest extends FormRequest
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
            'question_id' => 'required|string',
            'answer' => 'required|string|min:10|max:1000',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'question_id' => __('validation.attributes.question_id'),
            'answer' => __('validation.attributes.answer'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'question_id.required' => __('validation.required', ['attribute' => __('validation.attributes.question_id')]),
            'answer.required' => __('validation.required', ['attribute' => __('validation.attributes.answer')]),
            'answer.min' => __('validation.min.string', ['attribute' => __('validation.attributes.answer'), 'min' => 10]),
            'answer.max' => __('validation.max.string', ['attribute' => __('validation.attributes.answer'), 'max' => 1000]),
        ];
    }
}
