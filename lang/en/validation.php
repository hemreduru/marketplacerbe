<?php

return [
    // General validation messages
    'required' => 'The :attribute field is required.',
    'integer' => 'The :attribute must be an integer.',
    'string' => 'The :attribute must be a string.',
    'email' => 'The :attribute must be a valid email address.',
    'unique' => 'The :attribute has already been taken.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'max' => [
        'string' => 'The :attribute may not be greater than :max characters.',
    ],
    'in' => 'The selected :attribute is invalid.',

    // Custom attribute names
    'attributes' => [
        'package_id' => 'package ID',
        'status' => 'status',
        'question_id' => 'question ID',
        'answer' => 'answer',
        'name' => 'name',
        'email' => 'email',
        'current_password' => 'current password',
        'password' => 'password',
        'tracking_number' => 'tracking number',
    ],
];
