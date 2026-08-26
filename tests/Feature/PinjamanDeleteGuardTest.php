<?php

use App\Livewire\Pinjaman\Index;
use App\Models\Anggota;
use App\Models\AngsuranPinjaman;
use App\Models\Desa;
use App\Models\Pinjaman;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $this->desa = Desa::factory()->create();
    $this->admin = User::factory()->adminDesa($this->desa->id, $this->desa->kecamatan_id)->create();

    $this->anggota = Anggota::create([
        'desa_id' => $this->desa->id,
        'nama' => 'JUMADI',
        'nik' => '5555666677778888',
        'tanggal_gabung' => '2024-01-01',
        'status' => 'aktif',
    ]);
});

function buatPinjamanGuard($ctx, array $override = []): Pinjaman
{
    return Pinjaman::create(array_merge([
        'desa_id' => $ctx->desa->id,
        'anggota_id' => $ctx->anggota->id,
        'nomor_pinjaman' => 'PJM/GUARD/'.uniqid(),
        'tanggal_pinjaman' => '2026-01-15',
        'jumlah_pinjaman' => 1200000,
        'jangka_waktu_bulan' => 12,
        'jasa_persen' => 1,
        'status_pinjaman' => 'aktif',
    ], $override));
}

it('menolak menghapus pinjaman yang punya riwayat angsuran', function () {
    $pinjaman = buatPinjamanGuard($this);
    AngsuranPinjaman::create([
        'pinjaman_id' => $pinjaman->id,
        'tanggal_bayar' => '2026-02-15',
        'angsuran_ke' => 1,
        'pokok_dibayar' => 100000,
        'jasa_dibayar' => 12000,
        'denda_dibayar' => 0,
        'total_dibayar' => 112000,
    ]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('delete', $pinjaman->id)
        ->assertDispatched('error');

    expect(Pinjaman::withoutGlobalScopes()->find($pinjaman->id))->not->toBeNull();
});

it('menolak menghapus pinjaman hasil impor walau tanpa angsuran', function () {
    $pinjaman = buatPinjamanGuard($this, ['sumber' => 'import_excel', 'no_sppk' => 999]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('delete', $pinjaman->id)
        ->assertDispatched('error');

    expect(Pinjaman::withoutGlobalScopes()->find($pinjaman->id))->not->toBeNull();
});

it('mengizinkan menghapus pinjaman salah input yang belum punya jejak apa pun', function () {
    $pinjaman = buatPinjamanGuard($this);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('delete', $pinjaman->id)
        ->assertDispatched('success');

    expect(Pinjaman::withoutGlobalScopes()->find($pinjaman->id))->toBeNull();
});

it('admin desa lain tidak dapat menghapus pinjaman bukan desanya', function () {
    $pinjaman = buatPinjamanGuard($this);
    $desaLain = Desa::factory()->create();
    $adminLain = User::factory()->adminDesa($desaLain->id, $desaLain->kecamatan_id)->create();

    // Global scope desa membuat pinjaman milik desa lain tidak pernah
    // ditemukan (404 dari findOrFail) - lapisan pertahanan pertama.
    Livewire::actingAs($adminLain)
        ->test(Index::class)
        ->call('delete', $pinjaman->id);
})->throws(ModelNotFoundException::class);
