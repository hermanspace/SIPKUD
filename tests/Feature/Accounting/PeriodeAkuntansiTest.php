<?php

use App\Models\Akun;
use App\Models\Desa;
use App\Models\NeracaSaldo;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->desa = Desa::factory()->create();
    $this->user = User::factory()->adminDesa($this->desa->id, $this->desa->kecamatan_id)->create();
    $this->actingAs($this->user);

    $this->akunKas = Akun::factory()->tipe('aset')->create(['nama_akun' => 'Kas']);
    $this->akunPendapatan = Akun::factory()->tipe('pendapatan')->create(['nama_akun' => 'Pendapatan Jasa']);

    $this->service = app(AccountingService::class);
});

function periodeJurnalData(string $tanggal, float $jumlah = 100000, array $overrides = []): array
{
    return array_merge([
        'desa_id' => test()->desa->id,
        'tanggal_transaksi' => $tanggal,
        'jenis_jurnal' => 'memorial',
        'keterangan' => 'Transaksi periode',
        'status' => 'posted',
        'details' => [
            ['akun_id' => test()->akunKas->id, 'posisi' => 'debit', 'jumlah' => $jumlah],
            ['akun_id' => test()->akunPendapatan->id, 'posisi' => 'kredit', 'jumlah' => $jumlah],
        ],
    ], $overrides);
}

it('mengunci periode melalui closePeriod', function () {
    $this->service->createJurnal(periodeJurnalData('2026-03-05'));

    expect($this->service->isPeriodClosed($this->desa->id, '2026-03'))->toBeFalse();

    $this->service->closePeriod($this->desa->id, '2026-03');

    expect($this->service->isPeriodClosed($this->desa->id, '2026-03'))->toBeTrue();
});

it('menolak pembuatan jurnal baru pada periode yang sudah dikunci', function () {
    $this->service->createJurnal(periodeJurnalData('2026-03-05'));
    $this->service->closePeriod($this->desa->id, '2026-03');

    expect(fn () => $this->service->createJurnal(periodeJurnalData('2026-03-20')))
        ->toThrow(ValidationException::class);
});

it('mengizinkan jurnal saldo awal pada periode dikunci lewat allowClosedPeriod', function () {
    $this->service->createJurnal(periodeJurnalData('2026-03-05'));
    $this->service->closePeriod($this->desa->id, '2026-03');

    $jurnal = $this->service->createJurnal(
        periodeJurnalData('2026-03-01', 50000, ['keterangan' => 'Saldo awal']),
        allowClosedPeriod: true
    );

    expect($jurnal->exists)->toBeTrue();
});

it('menolak void jurnal pada periode yang sudah dikunci', function () {
    $jurnal = $this->service->createJurnal(periodeJurnalData('2026-03-05'));
    $this->service->closePeriod($this->desa->id, '2026-03');

    expect(fn () => $this->service->voidJurnal($jurnal))
        ->toThrow(ValidationException::class);
});

it('menolak penguncian periode saat masih ada jurnal draft', function () {
    $this->service->createJurnal(periodeJurnalData('2026-03-05'));
    $this->service->createJurnal(periodeJurnalData('2026-03-06', 100000, ['status' => 'draft']));

    expect(fn () => $this->service->closePeriod($this->desa->id, '2026-03'))
        ->toThrow(ValidationException::class);
});

it('membuka kembali periode melalui reopenPeriod', function () {
    $this->service->createJurnal(periodeJurnalData('2026-03-05'));
    $this->service->closePeriod($this->desa->id, '2026-03');
    $this->service->reopenPeriod($this->desa->id, '2026-03');

    expect($this->service->isPeriodClosed($this->desa->id, '2026-03'))->toBeFalse();

    $jurnal = $this->service->createJurnal(periodeJurnalData('2026-03-25'));
    expect($jurnal->exists)->toBeTrue();
});

it('membawa saldo akhir sebagai saldo awal periode berikutnya saat close', function () {
    $this->service->createJurnal(periodeJurnalData('2026-03-05', 250000));
    $this->service->closePeriod($this->desa->id, '2026-03');

    $ledgerApril = NeracaSaldo::withoutGlobalScopes()
        ->where('desa_id', $this->desa->id)
        ->where('akun_id', $this->akunKas->id)
        ->where('periode', '2026-04')
        ->first();

    expect($ledgerApril)->not->toBeNull()
        ->and((float) $ledgerApril->saldo_awal_debit)->toBe(250000.0)
        ->and((float) $ledgerApril->mutasi_debit)->toBe(0.0)
        ->and((float) $ledgerApril->saldo_akhir_debit)->toBe(250000.0)
        ->and($ledgerApril->status_periode)->toBe('open');
});

it('menandai neraca saldo sebagai closed beserta metadata penutup', function () {
    $this->service->createJurnal(periodeJurnalData('2026-03-05'));
    $this->service->closePeriod($this->desa->id, '2026-03');

    $ledger = NeracaSaldo::withoutGlobalScopes()
        ->where('desa_id', $this->desa->id)
        ->where('periode', '2026-03')
        ->get();

    expect($ledger)->not->toBeEmpty();
    $ledger->each(function ($row) {
        expect($row->status_periode)->toBe('closed')
            ->and($row->closed_at)->not->toBeNull()
            ->and($row->closed_by)->toBe($this->user->id);
    });
});
