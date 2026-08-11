<?php

use App\Models\Akun;
use App\Models\Desa;
use App\Models\Jurnal;
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

function jurnalData(array $overrides = []): array
{
    $desaId = test()->desa->id;

    return array_merge([
        'desa_id' => $desaId,
        'tanggal_transaksi' => '2026-03-10',
        'jenis_jurnal' => 'memorial',
        'keterangan' => 'Transaksi uji',
        'status' => 'posted',
        'details' => [
            [
                'akun_id' => test()->akunKas->id,
                'posisi' => 'debit',
                'jumlah' => 150000,
            ],
            [
                'akun_id' => test()->akunPendapatan->id,
                'posisi' => 'kredit',
                'jumlah' => 150000,
            ],
        ],
    ], $overrides);
}

it('membuat jurnal balance dengan detail dan nomor jurnal otomatis', function () {
    $jurnal = $this->service->createJurnal(jurnalData());

    expect($jurnal->status)->toBe('posted')
        ->and($jurnal->details)->toHaveCount(2)
        ->and((float) $jurnal->total_debit)->toBe(150000.0)
        ->and((float) $jurnal->total_kredit)->toBe(150000.0)
        ->and($jurnal->nomor_jurnal)->toMatch('#^JRN/\d{4}/\d{2}/\d{5}$#')
        ->and($jurnal->created_by)->toBe($this->user->id);
});

it('menolak jurnal yang tidak balance', function () {
    $data = jurnalData();
    $data['details'][1]['jumlah'] = 100000;

    expect(fn () => $this->service->createJurnal($data))
        ->toThrow(ValidationException::class);

    expect(Jurnal::count())->toBe(0);
});

it('menolak jurnal dengan kurang dari dua baris detail', function () {
    $data = jurnalData();
    $data['details'] = [$data['details'][0]];

    expect(fn () => $this->service->createJurnal($data))
        ->toThrow(ValidationException::class);
});

it('menolak jurnal yang memakai akun nonaktif', function () {
    $akunNonaktif = Akun::factory()->tipe('beban')->nonaktif()->create();

    $data = jurnalData();
    $data['details'][0]['akun_id'] = $akunNonaktif->id;

    expect(fn () => $this->service->createJurnal($data))
        ->toThrow(ValidationException::class);
});

it('memposting jurnal ke neraca saldo (ledger) dengan mutasi dan saldo akhir yang benar', function () {
    $this->service->createJurnal(jurnalData());

    $ledgerKas = NeracaSaldo::withoutGlobalScopes()
        ->where('desa_id', $this->desa->id)
        ->where('akun_id', $this->akunKas->id)
        ->where('periode', '2026-03')
        ->first();

    $ledgerPendapatan = NeracaSaldo::withoutGlobalScopes()
        ->where('desa_id', $this->desa->id)
        ->where('akun_id', $this->akunPendapatan->id)
        ->where('periode', '2026-03')
        ->first();

    expect($ledgerKas)->not->toBeNull()
        ->and((float) $ledgerKas->mutasi_debit)->toBe(150000.0)
        ->and((float) $ledgerKas->mutasi_kredit)->toBe(0.0)
        ->and((float) $ledgerKas->saldo_akhir_debit)->toBe(150000.0);

    expect($ledgerPendapatan)->not->toBeNull()
        ->and((float) $ledgerPendapatan->mutasi_kredit)->toBe(150000.0)
        ->and((float) $ledgerPendapatan->saldo_akhir_kredit)->toBe(150000.0);
});

it('mengakumulasi mutasi ledger saat ada beberapa jurnal dalam periode yang sama', function () {
    $this->service->createJurnal(jurnalData());
    $this->service->createJurnal(jurnalData(['keterangan' => 'Transaksi kedua']));

    $ledgerKas = NeracaSaldo::withoutGlobalScopes()
        ->where('akun_id', $this->akunKas->id)
        ->where('periode', '2026-03')
        ->first();

    expect((float) $ledgerKas->mutasi_debit)->toBe(300000.0)
        ->and((float) $ledgerKas->saldo_akhir_debit)->toBe(300000.0);
});

it('tidak memposting jurnal draft ke ledger', function () {
    $this->service->createJurnal(jurnalData(['status' => 'draft']));

    expect(NeracaSaldo::withoutGlobalScopes()->count())->toBe(0);
});

it('memposting jurnal draft ke ledger melalui postJurnal', function () {
    $jurnal = $this->service->createJurnal(jurnalData(['status' => 'draft']));

    $this->service->postJurnal($jurnal);

    expect($jurnal->fresh()->status)->toBe('posted')
        ->and(NeracaSaldo::withoutGlobalScopes()->where('akun_id', $this->akunKas->id)->exists())->toBeTrue();
});

it('menolak update jurnal yang sudah posted', function () {
    $jurnal = $this->service->createJurnal(jurnalData());

    expect(fn () => $this->service->updateJurnal($jurnal, jurnalData()))
        ->toThrow(ValidationException::class);
});

it('mengizinkan update jurnal draft dan menghitung ulang total', function () {
    $jurnal = $this->service->createJurnal(jurnalData(['status' => 'draft']));

    $data = jurnalData(['status' => 'draft', 'keterangan' => 'Setelah koreksi']);
    $data['details'][0]['jumlah'] = 200000;
    $data['details'][1]['jumlah'] = 200000;

    $updated = $this->service->updateJurnal($jurnal, $data);

    expect((float) $updated->total_debit)->toBe(200000.0)
        ->and($updated->keterangan)->toBe('Setelah koreksi')
        ->and($updated->details)->toHaveCount(2);
});

it('mengizinkan dua desa berbeda memiliki nomor jurnal yang sama', function () {
    $jurnalA = $this->service->createJurnal(jurnalData());

    $desaLain = Desa::factory()->create();
    $adminLain = User::factory()->adminDesa($desaLain->id, $desaLain->kecamatan_id)->create();
    $this->actingAs($adminLain);

    $jurnalB = $this->service->createJurnal(jurnalData(['desa_id' => $desaLain->id]));

    expect($jurnalB->nomor_jurnal)->toBe($jurnalA->nomor_jurnal)
        ->and($jurnalB->desa_id)->not->toBe($jurnalA->desa_id);
});

it('membatalkan jurnal melalui voidJurnal', function () {
    $jurnal = $this->service->createJurnal(jurnalData());

    $this->service->voidJurnal($jurnal);

    expect($jurnal->fresh()->status)->toBe('void');
});
