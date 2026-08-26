<?php

use App\Livewire\Angsuran\Create;
use App\Models\Anggota;
use App\Models\AngsuranPinjaman;
use App\Models\Desa;
use App\Models\Pinjaman;
use App\Models\User;

beforeEach(function () {
    $this->desa = Desa::factory()->create();
    $this->admin = User::factory()->adminDesa($this->desa->id, $this->desa->kecamatan_id)->create();

    $anggota = Anggota::create([
        'desa_id' => $this->desa->id,
        'nama' => 'HASAN BASRI',
        'nik' => '1111222233334444',
        'tanggal_gabung' => '2024-01-01',
        'status' => 'aktif',
    ]);

    $this->pinjaman = Pinjaman::create([
        'desa_id' => $this->desa->id,
        'anggota_id' => $anggota->id,
        'nomor_pinjaman' => 'PJM/2024/001',
        'tanggal_pinjaman' => '2024-02-01',
        'jumlah_pinjaman' => 1200000,
        'jangka_waktu_bulan' => 12,
        'jasa_persen' => 1,
        'status_pinjaman' => 'aktif',
    ]);

    foreach ([1, 2] as $ke) {
        AngsuranPinjaman::create([
            'pinjaman_id' => $this->pinjaman->id,
            'tanggal_bayar' => '2024-0'.($ke + 2).'-01',
            'angsuran_ke' => $ke,
            'pokok_dibayar' => 100000,
            'jasa_dibayar' => 12000,
            'denda_dibayar' => 0,
            'total_dibayar' => 112000,
        ]);
    }
});

it('menampilkan kartu pinjaman lengkap dengan riwayat angsuran', function () {
    $this->actingAs($this->admin)
        ->get(route('pinjaman.show', $this->pinjaman))
        ->assertOk()
        ->assertSee('HASAN BASRI')
        ->assertSee('PJM/2024/001')
        ->assertSee('Riwayat Angsuran')
        ->assertSee('Bayar Angsuran')       // tombol untuk admin desa, pinjaman aktif
        ->assertSee('Kolektibilitas');
});

it('admin kecamatan dapat melihat detail tanpa tombol bayar', function () {
    $kecamatan = User::factory()->adminKecamatan($this->desa->kecamatan_id)->create();

    $this->actingAs($kecamatan)
        ->get(route('pinjaman.show', $this->pinjaman))
        ->assertOk()
        ->assertSee('HASAN BASRI')
        ->assertDontSee('Bayar Angsuran');
});

it('admin desa lain tidak dapat melihat pinjaman bukan desanya', function () {
    $desaLain = Desa::factory()->create();
    $adminLain = User::factory()->adminDesa($desaLain->id, $desaLain->kecamatan_id)->create();

    $this->actingAs($adminLain)
        ->get(route('pinjaman.show', $this->pinjaman))
        ->assertNotFound();
});

it('route pinjaman/create tidak tertelan route detail', function () {
    $this->actingAs($this->admin)
        ->get('/pinjaman/create')
        ->assertOk();
});

it('tombol bayar membuka form angsuran dengan pinjaman terpilih', function () {
    Livewire\Livewire::actingAs($this->admin)
        ->withQueryParams(['pinjaman' => $this->pinjaman->id])
        ->test(Create::class)
        ->assertSet('pinjaman_id', $this->pinjaman->id);
});
