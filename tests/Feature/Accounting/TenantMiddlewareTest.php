<?php

use App\Models\Desa;
use App\Models\Kelompok;
use App\Models\User;

it('menolak admin desa tanpa desa_id mengakses halaman data desa', function () {
    $user = User::factory()->create([
        'role' => 'admin_desa',
        'desa_id' => null,
        'kecamatan_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('kas.index'))
        ->assertForbidden();
});

it('menolak admin kecamatan tanpa kecamatan_id mengakses halaman pengguna', function () {
    $user = User::factory()->create([
        'role' => 'admin_kecamatan',
        'desa_id' => null,
        'kecamatan_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('pengguna.index'))
        ->assertForbidden();
});

it('menolak admin desa membuka halaman edit milik desa lain berdasarkan scope', function () {
    $desaA = Desa::factory()->create();
    $desaB = Desa::factory()->create();

    $adminA = User::factory()->adminDesa($desaA->id, $desaA->kecamatan_id)->create();
    $adminB = User::factory()->adminDesa($desaB->id, $desaB->kecamatan_id)->create();

    $this->actingAs($adminB);
    $kelompokB = Kelompok::create([
        'desa_id' => $desaB->id,
        'nama_kelompok' => 'Kelompok Desa B',
        'status' => 'aktif',
    ]);

    // Admin desa A tidak boleh menemukan kelompok milik desa B (404 via scope)
    $this->actingAs($adminA)
        ->get(route('kelompok.edit', $kelompokB->id))
        ->assertNotFound();
});
