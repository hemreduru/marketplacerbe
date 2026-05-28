<?php

use App\Services\EFatura\BizimHesap\BizimHesapService;
use App\Services\EFatura\Gib\GibService;
use App\Services\EFatura\Parasut\ParasutService;

return [

    'providers' => [
        'parasut' => [
            'name' => 'Paraşüt',
            'class' => ParasutService::class,
            'base_url' => env('PARASUT_BASE_URL', 'https://api.parasut.com/v4'),
            'enabled' => true,
        ],
        'bizim_hesap' => [
            'name' => 'BizimHesap',
            'class' => BizimHesapService::class,
            'base_url' => env('BIZIM_HESAP_BASE_URL', 'https://api.bizimhesap.com'),
            'enabled' => false,
        ],
        'gib_direct' => [
            'name' => 'GIB e-Arşiv',
            'class' => GibService::class,
            'base_url' => env('GIB_EFATURA_BASE_URL'),
            'enabled' => false,
        ],
    ],

    'default_vat_rate' => 20,

    'company_defaults' => [
        'invoice_series' => 'CIRO',
    ],
];
