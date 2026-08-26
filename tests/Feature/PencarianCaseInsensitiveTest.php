<?php

use App\Livewire\Pinjaman\Index;
use App\Models\Anggota;
use App\Models\AngsuranPinjaman;
use App\Models\Desa;
use App\Models\Pinjaman;
use App\Models\User;
use Livewire\Livewire;

/**
 * Data hasil impor dari Excel lama seluruhnya huruf kapital. Pencarian harus
 * tetap menemukannya walau diketik huruf kecil (di PostgreSQL, LIKE biasa
 * bersifat case-sensitive - regresi ini nyata di produksi).
 */
beforeEach(function () {
    $this->desa = Desa::factory()->create();
    $this->admin = User::factory()->adminDesa($this->desa->id, $this->desa->kecamatan_id)->create();

    $this->anggota = Anggota::create([
        'desa_id' => $this->desa->id,
        'nama' => 'MARWAN SAPUTRA',
        'nik' => '1234567890123456',
        'tanggal_gabung' => '2024-01-01',
        'status' => 'aktif',
    ]);

    $this->pinjaman = Pinjaman::create([
        'desa_id' => $this->desa->id,
        'anggota_id' => $this->anggota->id,
        'nomor_pinjaman' => 'IMP/TEST/77',
        'tanggal_pinjaman' => '2024-02-01',
        'jumlah_pinjaman' => 1200000,
        'jangka_waktu_bulan' => 12,
        'jasa_persen' => 1,
        'status_pinjaman' => 'aktif',
    ]);

    AngsuranPinjaman::create([
        'pinjaman_id' => $this->pinjaman->id,
        'tanggal_bayar' => '2024-03-01',
        'angsuran_ke' => 1,
        'pokok_dibayar' => 100000,
        'jasa_dibayar' => 12000,
        'denda_dibayar' => 0,
        'total_dibayar' => 112000,
    ]);
});

it('pencarian anggota menemukan nama kapital dengan ketikan huruf kecil', function () {
    Livewire::actingAs($this->admin)
        ->test(App\Livewire\MasterData\Anggota\Index::class)
        ->set('search', 'marwan')
        ->assertSee('MARWAN SAPUTRA');
});

it('pencarian pinjaman menemukan nama anggota dengan ketikan huruf kecil', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('search', 'marwan')
        ->assertSee('MARWAN SAPUTRA');

    // juga via nomor pinjaman huruf kecil
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('search', 'imp/test')
        ->assertSee('IMP/TEST/77');
});

it('pencarian angsuran menemukan nama anggota dengan ketikan huruf kecil', function () {
    Livewire::actingAs($this->admin)
        ->test(App\Livewire\Angsuran\Index::class)
        ->set('search', 'marwan')
        ->assertSee('MARWAN SAPUTRA');
});
