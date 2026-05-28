<?php

use App\Support\ServiceResult;

test('ok() boş data ile başarılı sonuç üretir', function () {
    $result = ServiceResult::ok();

    expect($result->ok)->toBeTrue()
        ->and($result->data)->toBeNull()
        ->and($result->errorCode)->toBeNull()
        ->and($result->errorMessage)->toBeNull()
        ->and($result->raw)->toBeNull();
});

test('ok() verilen payload\'ı data alanına yerleştirir', function (mixed $payload) {
    $result = ServiceResult::ok($payload);

    expect($result->ok)->toBeTrue()
        ->and($result->data)->toBe($payload);
})->with([
    'array' => [['count' => 5, 'items' => ['a', 'b']]],
    'string' => ['hello'],
    'integer' => [42],
    'float' => [3.14],
    'true' => [true],
    'false' => [false],
    'null' => [null],
    'object' => [(object) ['foo' => 'bar']],
]);

test('fail() code ve message ile hata sonucu üretir', function () {
    $result = ServiceResult::fail('rate_limited', 'Too many requests');

    expect($result->ok)->toBeFalse()
        ->and($result->errorCode)->toBe('rate_limited')
        ->and($result->errorMessage)->toBe('Too many requests')
        ->and($result->data)->toBeNull()
        ->and($result->raw)->toBeNull();
});

test('fail() opsiyonel raw response saklar', function () {
    $raw = ['status' => 429, 'headers' => ['Retry-After' => '60']];

    $result = ServiceResult::fail('rate_limited', 'Too many requests', $raw);

    expect($result->ok)->toBeFalse()
        ->and($result->raw)->toBe($raw);
});

test('direkt constructor da kullanılabilir', function () {
    $result = new ServiceResult(ok: true, data: ['x' => 1]);

    expect($result->ok)->toBeTrue()
        ->and($result->data)->toBe(['x' => 1]);
});

test('readonly property yeniden atanamaz', function () {
    $result = ServiceResult::ok('first');

    expect(fn () => $result->data = 'second')->toThrow(Error::class);
});
