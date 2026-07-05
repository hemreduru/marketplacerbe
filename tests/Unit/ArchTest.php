<?php

/*
 * Mimari kurallar — finansal kodda float güvenliği.
 *
 * Para hesapları yalnızca bcmath ile yapılır (Spec Bölüm 0 Madde 5):
 * round/number_format/floatval sonuçları float'a düşürür ve kuruş
 * hataları yaratır; bcround/bcadd/bcmul/bcdiv kullanılmalıdır.
 */

arch('hesaplama servisleri float fonksiyonları kullanmaz')
    ->expect('App\Services\Calculations')
    ->not->toUse(['round', 'number_format', 'floatval', 'fdiv', 'fmod', 'intdiv', 'abs', 'floor', 'ceil']);

arch('finance servisleri float fonksiyonları kullanmaz')
    ->expect('App\Services\Finance')
    ->not->toUse(['round', 'number_format', 'floatval', 'fdiv', 'fmod', 'intdiv']);

arch('para value objectleri float fonksiyonları kullanmaz')
    ->expect(['App\Support\MoneyAllocator', 'App\Support\ProfitBreakdown', 'App\Support\MarketplaceFeeProfile'])
    ->not->toUse(['round', 'number_format', 'floatval']);

arch('calculations katmanı HTTP veya DB facade kullanmaz')
    ->expect('App\Services\Calculations')
    ->not->toUse(['Illuminate\Support\Facades\Http', 'Illuminate\Support\Facades\DB']);
