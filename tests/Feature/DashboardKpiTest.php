<?php

use App\Models\Desa;
use App\Models\User;

it('menampilkan KPI keuangan di dashboard super admin', function () {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Ringkasan Keuangan')
        ->assertSee('Sisa Pinjaman Beredar')
        ->assertSee('NPL (Pinjaman Bermasalah)');
});

it('menampilkan KPI keuangan di dashboard admin kecamatan', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->adminKecamatan($desa->kecamatan_id)->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Ringkasan Keuangan')
        ->assertSee('Saldo Kas');
});

it('menampilkan KPI keuangan di dashboard admin desa dan executive view', function () {
    $desa = Desa::factory()->create();

    $this->actingAs(User::factory()->adminDesa($desa->id, $desa->kecamatan_id)->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Ringkasan Keuangan');

    $executive = User::factory()->create([
        'role' => 'executive_view',
        'desa_id' => $desa->id,
        'kecamatan_id' => $desa->kecamatan_id,
    ]);

    $this->actingAs($executive)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Mode Baca Saja (Executive View)')
        ->assertSee('Ringkasan Keuangan')
        ->assertSee('Laba Berjalan');
});
