<?php

use App\Models\Akun;
use App\Models\Desa;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->desa = Desa::factory()->create();
    $this->user = User::factory()->adminDesa($this->desa->id, $this->desa->kecamatan_id)->create();
    $this->actingAs($this->user);

    $this->akunKas = Akun::factory()->tipe('aset')->create();
    $this->akunPendapatan = Akun::factory()->tipe('pendapatan')->create();

    app(AccountingService::class)->createJurnal([
        'desa_id' => $this->desa->id,
        'tanggal_transaksi' => '2026-03-10',
        'jenis_jurnal' => 'memorial',
        'keterangan' => 'Transaksi valid',
        'status' => 'posted',
        'details' => [
            ['akun_id' => $this->akunKas->id, 'posisi' => 'debit', 'jumlah' => 100000],
            ['akun_id' => $this->akunPendapatan->id, 'posisi' => 'kredit', 'jumlah' => 100000],
        ],
    ]);
});

it('lolos verifikasi saat data akuntansi konsisten', function () {
    $this->artisan('accounting:verify-integrity')
        ->expectsOutputToContain('Integritas akuntansi OK')
        ->assertExitCode(0);
});

it('mendeteksi jurnal yang tidak balance', function () {
    DB::table('jurnal')->update(['total_kredit' => 50000]);

    $this->artisan('accounting:verify-integrity')->assertExitCode(1);
});

it('mendeteksi header jurnal yang tidak sesuai dengan detail', function () {
    DB::table('jurnal_detail')
        ->where('posisi', 'debit')
        ->update(['jumlah' => 75000]);

    $this->artisan('accounting:verify-integrity')->assertExitCode(1);
});

it('mendeteksi mutasi neraca saldo yang tidak sesuai jurnal', function () {
    DB::table('neraca_saldo')
        ->where('akun_id', $this->akunKas->id)
        ->update(['mutasi_debit' => 999999, 'saldo_akhir_debit' => 999999]);

    $this->artisan('accounting:verify-integrity')->assertExitCode(1);
});

it('mendeteksi saldo akhir yang tidak sama dengan saldo awal plus mutasi', function () {
    DB::table('neraca_saldo')
        ->where('akun_id', $this->akunKas->id)
        ->update(['saldo_akhir_debit' => 1]);

    $this->artisan('accounting:verify-integrity')->assertExitCode(1);
});

it('mendeteksi jurnal posted yang belum diposting ke neraca saldo', function () {
    DB::table('neraca_saldo')->delete();

    $this->artisan('accounting:verify-integrity')->assertExitCode(1);
});
