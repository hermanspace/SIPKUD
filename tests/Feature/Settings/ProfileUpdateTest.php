<?php

use App\Livewire\Settings\Profile;
use App\Models\User;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/settings/profile')->assertOk();
});

test('super admin dapat mengubah informasi profil', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('nama', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->nama)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('non super admin tidak dapat mengubah informasi profil', function () {
    $user = User::factory()->create(); // role default: admin_desa
    $namaAwal = $user->nama;

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('nama', 'Nama Baru')
        ->call('updateProfileInformation');

    expect($user->refresh()->nama)->toEqual($namaAwal);
});

test('status verifikasi email tidak berubah jika email tidak berubah', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('nama', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('super admin dapat menghapus akunnya jika bukan super admin terakhir', function () {
    User::factory()->superAdmin()->create(); // super admin lain agar bukan yang terakhir
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user);

    $response = Livewire::test('settings.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('password yang benar harus diberikan untuk menghapus akun', function () {
    User::factory()->superAdmin()->create();
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user);

    $response = Livewire::test('settings.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});

test('non super admin tidak dapat menghapus akunnya sendiri', function () {
    $user = User::factory()->create(); // role default: admin_desa

    $this->actingAs($user);

    Livewire::test('settings.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    expect($user->fresh())->not->toBeNull();
});

test('super admin terakhir tidak dapat menghapus akunnya', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user);

    Livewire::test('settings.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    expect($user->fresh())->not->toBeNull();
});
