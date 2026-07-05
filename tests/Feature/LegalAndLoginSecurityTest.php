<?php

use App\Models\User;

test('yasal sayfalar public olarak render olur', function (string $page, string $titleKey) {
    $this->get(route('legal.show', $page))
        ->assertOk()
        ->assertSee(__($titleKey));
})->with([
    'privacy' => ['privacy', 'legal.privacy_title'],
    'terms' => ['terms', 'legal.terms_title'],
    'distance-sales' => ['distance-sales', 'legal.distance_sales_title'],
]);

test('geçersiz yasal sayfa 404 döner', function () {
    $this->get('/legal/nonexistent')->assertNotFound();
});

test('login 5 başarısız denemeden sonra kilitlenir', function () {
    User::factory()->create(['email' => 'lock@test.com']); // factory şifresi: password

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login.post'), [
            'email' => 'lock@test.com',
            'password' => 'yanlis-sifre',
        ]);
    }

    // 6. deneme DOĞRU şifreyle bile kilitli — brute-force koruması devrede.
    $this->post(route('login.post'), [
        'email' => 'lock@test.com',
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('başarılı giriş rate limiter\'ı sıfırlar', function () {
    User::factory()->create(['email' => 'ok@test.com']);

    // 4 başarısız (kilit eşiği 5) sonra doğru şifre geçmeli.
    for ($i = 0; $i < 4; $i++) {
        $this->post(route('login.post'), ['email' => 'ok@test.com', 'password' => 'yanlis']);
    }

    $this->post(route('login.post'), ['email' => 'ok@test.com', 'password' => 'password'])
        ->assertRedirect();

    $this->assertAuthenticated();
});
