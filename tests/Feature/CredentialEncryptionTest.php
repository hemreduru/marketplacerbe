<?php

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Pazaryeri credential sırları rest'te şifreli; model üzerinden şeffaf çözülür.
 */
test('api_key ve api_secret rest\'te şifreli saklanır, model\'de şeffaf çözülür', function () {
    [, $credential] = userWithTrendyol(credentialAttributes: [
        'api_key' => 'plain-key-123',
        'api_secret' => 'plain-secret-456',
    ]);

    // Model üzerinden okuma plaintext döner (cast çözer).
    expect($credential->fresh()->api_key)->toBe('plain-key-123')
        ->and($credential->fresh()->api_secret)->toBe('plain-secret-456');

    // Ham DB değeri şifreli — plaintext DEĞİL, ama çözülebilir.
    $raw = DB::table('user_marketplace_credentials')->where('id', $credential->id)->first();
    expect($raw->api_key)->not->toBe('plain-key-123')
        ->and($raw->api_secret)->not->toBe('plain-secret-456')
        ->and(Crypt::decryptString($raw->api_key))->toBe('plain-key-123')
        ->and(Crypt::decryptString($raw->api_secret))->toBe('plain-secret-456');
});

test('additional_credentials şifreli array olarak saklanır (webhook_secret dahil)', function () {
    [, $credential] = userWithTrendyol(credentialAttributes: [
        'additional_credentials' => ['seller_id' => '342591', 'webhook_secret' => 's3cr3t'],
    ]);

    // Model array döner (cast çözer + json_decode).
    expect($credential->fresh()->additional_credentials)
        ->toMatchArray(['seller_id' => '342591', 'webhook_secret' => 's3cr3t']);

    // Ham DB değeri şifreli — düz JSON değil.
    $raw = DB::table('user_marketplace_credentials')->where('id', $credential->id)->value('additional_credentials');
    expect($raw)->not->toContain('342591')
        ->and($raw)->not->toContain('s3cr3t')
        ->and(Crypt::decryptString($raw))->toContain('342591');
});
