<?php

/*
 * Config smoke — .env.example'da dokümante edilen kritik anahtarların config
 * katmanında okunduğunu doğrular (kazara config kırılmasını yakalar).
 */

test('ödeme (iyzico) config yapısı tanımlı', function () {
    expect(config('services.iyzico'))->toBeArray()
        ->toHaveKeys(['api_key', 'secret_key', 'base_url', 'debug']);
});

test('mutabakat (finance) config anahtarları tanımlı', function () {
    expect(config('finance.return_window_days'))->not->toBeNull()
        ->and(config('finance.deviation_alert_threshold'))->not->toBeNull();
});
