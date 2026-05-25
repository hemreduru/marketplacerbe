<?php

test('the root path redirects guests to the login page', function () {
    $this->get('/')
        ->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});
