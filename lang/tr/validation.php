<?php

return [
    // General validation messages
    'required' => ':attribute alanı zorunludur.',
    'integer' => ':attribute bir sayı olmalıdır.',
    'string' => ':attribute metin olmalıdır.',
    'email' => ':attribute geçerli bir e-posta adresi olmalıdır.',
    'unique' => ':attribute daha önce kullanılmış.',
    'confirmed' => ':attribute doğrulaması eşleşmiyor.',
    'min' => [
        'string' => ':attribute en az :min karakter olmalıdır.',
    ],
    'max' => [
        'string' => ':attribute en fazla :max karakter olmalıdır.',
    ],
    'in' => 'Seçilen :attribute geçersiz.',

    // Custom attribute names
    'attributes' => [
        'package_id' => 'paket ID',
        'status' => 'durum',
        'question_id' => 'soru ID',
        'answer' => 'cevap',
        'name' => 'isim',
        'email' => 'e-posta',
        'current_password' => 'mevcut şifre',
        'password' => 'şifre',
        'tracking_number' => 'takip numarası',
    ],
];
