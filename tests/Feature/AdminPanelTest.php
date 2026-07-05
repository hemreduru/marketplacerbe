<?php

use App\Models\User;

function adminUser(): User
{
    [$admin] = userWithTrendyol();
    $admin->update(['is_admin' => true]);

    return $admin->fresh();
}

test('admin olmayan kullanıcı admin paneline erişemez', function () {
    [$user] = userWithTrendyol();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
});

test('admin dashboard sistem metriklerini gösterir', function () {
    $this->actingAs(adminUser())->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(__('admin.metric_users'))
        ->assertSee(__('admin.recent_payments'))
        ->assertSee(__('admin.recent_activity'));
});

test('admin kullanıcı listesini görür', function () {
    User::factory()->create(['email' => 'seller@test.com']);

    $this->actingAs(adminUser())->get(route('admin.users'))
        ->assertOk()
        ->assertSee('seller@test.com')
        ->assertSee(__('admin.impersonate'));
});

test('admin kullanıcıyı impersonate edip admin hesabına geri döner', function () {
    $admin = adminUser();
    $target = User::factory()->create();

    $this->actingAs($admin)->post(route('admin.impersonate', $target))
        ->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($target);
    expect(session('impersonator_id'))->toBe($admin->id);

    $this->post(route('stop-impersonating'))->assertRedirect(route('admin.users'));
    $this->assertAuthenticatedAs($admin);
    expect(session('impersonator_id'))->toBeNull();
});

test('admin başka bir admini impersonate edemez', function () {
    $admin = adminUser();
    $other = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->post(route('admin.impersonate', $other))->assertRedirect();
    $this->assertAuthenticatedAs($admin);
    expect(session('impersonator_id'))->toBeNull();
});
