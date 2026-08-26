<?php

use App\Livewire\MasterData\ImportHistoris\Index as ImportHistorisIndex;
use App\Livewire\Pinjaman\Create;
use App\Models\Akun;
use App\Models\Anggota;
use App\Models\AngsuranPinjaman;
use App\Models\Desa;
use App\Models\Jurnal;
use App\Models\Pinjaman;
use App\Models\TransaksiKas;
use App\Models\UnitUsaha;
use App\Models\User;
use App\Services\ImportHistorisService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

beforeEach(function () {
    Carbon::setTestNow('2026-08-26');

    $this->desa = Desa::factory()->create();
    $this->admin = User::factory()->adminDesa($this->desa->id, $this->desa->kecamatan_id)->create();
    $this->unit = UnitUsaha::factory()->create(['desa_id' => $this->desa->id]);
    $this->akunPiutang = Akun::factory()->create([
        'kode_akun' => '1-12-001', 'nama_akun' => 'Piutang Pinjaman', 'tipe_akun' => 'aset',
    ]);
    $this->akunModal = Akun::factory()->create([
        'kode_akun' => '3-10-001', 'nama_akun' => 'Modal Desa', 'tipe_akun' => 'ekuitas',
    ]);
});

function fixtureLapkeu(): string
{
    return base_path('tests/Fixtures/lapkeu-uek-sample.xls');
}

function importSampel($ctx): array
{
    $service = app(ImportHistorisService::class);

    return $service->import(
        desaId: $ctx->desa->id,
        parsed: $service->parse(fixtureLapkeu()),
        unitUsahaId: $ctx->unit->id,
        akunPiutangId: $ctx->akunPiutang->id,
        akunModalId: $ctx->akunModal->id,
        userId: $ctx->admin->id,
    );
}

it('membaca file LPP-UEK dengan ringkasan dan kontrol silang yang benar', function () {
    $parsed = app(ImportHistorisService::class)->parse(fixtureLapkeu());

    expect($parsed['periode'])->toBe('APRIL 2018')
        ->and($parsed['ringkasan']['jumlah_pinjaman'])->toBe(4)
        ->and($parsed['ringkasan']['jumlah_aktif'])->toBe(3)
        ->and($parsed['ringkasan']['jumlah_lunas'])->toBe(1)
        ->and($parsed['ringkasan']['jumlah_anggota'])->toBe(3)
        ->and($parsed['ringkasan']['total_sisa_pokok'])->toEqual(3200000.0)
        ->and($parsed['errors'])->toHaveCount(2)
        ->and($parsed['kontrol']['cocok'])->toBeTrue();
});

it('mengimpor anggota, pinjaman, angsuran sintetis, dan jurnal saldo awal', function () {
    $this->actingAs($this->admin);

    $hasil = importSampel($this);

    expect($hasil['anggota_baru'])->toBe(3)
        ->and($hasil['pinjaman_baru'])->toBe(4)
        ->and($hasil['angsuran_dibuat'])->toBe(12 + 6 + 19 + 0)
        ->and($hasil['sisa_pokok_dijurnal'])->toEqual(3200000.0)
        ->and($hasil['nomor_jurnal'])->not->toBeNull();

    // Anggota impor memakai NIK sementara 16 digit
    $siti = Anggota::withoutGlobalScopes()->where('nama', 'SITI AMINAH')->first();
    expect($siti)->not->toBeNull()
        ->and($siti->nik_sementara)->toBeTrue()
        ->and(strlen($siti->nik))->toBe(16);

    // BUDI punya dua pinjaman tapi satu anggota
    expect(Anggota::withoutGlobalScopes()->where('desa_id', $this->desa->id)->count())->toBe(3)
        ->and(Pinjaman::withoutGlobalScopes()->where('desa_id', $this->desa->id)->count())->toBe(4);

    // Jurnal memorial seimbang senilai sisa piutang; kas TIDAK tersentuh
    $jurnal = Jurnal::withoutGlobalScopes()->where('nomor_jurnal', $hasil['nomor_jurnal'])->first();
    expect((float) $jurnal->total_debit)->toEqual(3200000.0)
        ->and((float) $jurnal->total_kredit)->toEqual(3200000.0)
        ->and($jurnal->jenis_jurnal)->toBe('memorial')
        ->and(TransaksiKas::withoutGlobalScopes()->count())->toBe(0);
});

it('menghitung kolektibilitas dengan benar dari angsuran sintetis', function () {
    $this->actingAs($this->admin);
    importSampel($this);

    // SITI: tenor 12 sejak Agu 2024 (jatuh tempo lewat), baru 6 angsuran -> tunggakan 6 bulan
    $siti = Pinjaman::withoutGlobalScopes()->where('no_sppk', 2)->first();
    expect($siti->tunggakan_bulan)->toBe(6)
        ->and($siti->kolektibilitas)->toBe('diragukan');

    // RINA: pinjaman baru bulan ini, belum ada angsuran jatuh tempo -> lancar
    $rina = Pinjaman::withoutGlobalScopes()->where('no_sppk', 4)->first();
    expect($rina->kolektibilitas)->toBe('lancar');

    // BUDI pinjaman pertama lunas
    $budi1 = Pinjaman::withoutGlobalScopes()->where('no_sppk', 1)->first();
    expect($budi1->status_pinjaman)->toBe('lunas')
        ->and(AngsuranPinjaman::where('pinjaman_id', $budi1->id)->count())->toBe(12);
});

it('idempoten: unggah ulang file yang sama tidak menduplikasi apa pun', function () {
    $this->actingAs($this->admin);
    importSampel($this);
    $kedua = importSampel($this);

    expect($kedua['pinjaman_baru'])->toBe(0)
        ->and($kedua['pinjaman_dilewati'])->toBe(4)
        ->and($kedua['anggota_baru'])->toBe(0)
        ->and($kedua['sisa_pokok_dijurnal'])->toEqual(0.0)
        ->and($kedua['nomor_jurnal'])->toBeNull()
        ->and(Pinjaman::withoutGlobalScopes()->where('desa_id', $this->desa->id)->count())->toBe(4)
        ->and(Jurnal::withoutGlobalScopes()->where('desa_id', $this->desa->id)->count())->toBe(1);
});

it('alur Livewire lengkap: unggah, tinjau, tulis', function () {
    Livewire::actingAs($this->admin)
        ->test(ImportHistorisIndex::class)
        ->set('uploadFile', UploadedFile::fake()->createWithContent('lapkeu.xls', file_get_contents(fixtureLapkeu())))
        ->assertSet('pesanError', '')
        ->assertSet('step', 2)
        ->assertSee('Pemeriksaan silang')
        ->set('unit_usaha_id', $this->unit->id)
        ->set('akun_piutang_id', $this->akunPiutang->id)
        ->set('akun_modal_id', $this->akunModal->id)
        ->call('jalankan')
        ->assertSet('step', 3)
        ->assertSee('Impor Selesai');

    expect(Pinjaman::withoutGlobalScopes()->where('desa_id', $this->desa->id)->count())->toBe(4);
});

it('menolak file yang bukan template LPP-UEK', function () {
    $palsu = tempnam(sys_get_temp_dir(), 'xls');
    copy(fixtureLapkeu(), $palsu);

    // buat file xls valid tapi tanpa sheet LPP-UEK: pakai file kosong xlsx sederhana
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->setTitle('Sheet1')->setCellValue('A1', 'bukan template');
    (new Xls($spreadsheet))->save($palsu);

    app(ImportHistorisService::class)->parse($palsu);
})->throws(RuntimeException::class, 'LPP-UEK');

it('memblokir pinjaman baru untuk anggota dengan NIK sementara', function () {
    $this->actingAs($this->admin);
    importSampel($this);
    $siti = Anggota::withoutGlobalScopes()->where('nama', 'SITI AMINAH')->first();

    Livewire::actingAs($this->admin)
        ->test(Create::class)
        ->set('anggota_id', $siti->id)
        ->set('tanggal_pinjaman', '2026-08-26')
        ->set('jumlah_pinjaman', '1000000')
        ->set('jangka_waktu_bulan', '12')
        ->set('jasa_persen', '1')
        ->call('save')
        ->assertHasErrors('anggota_id');
});

it('halaman impor hanya untuk admin desa', function () {
    $this->actingAs($this->admin)->get(route('impor-historis.index'))->assertOk();

    $kecamatan = User::factory()->adminKecamatan($this->desa->kecamatan_id)->create();
    $this->actingAs($kecamatan)->get(route('impor-historis.index'))->assertForbidden();

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('impor-historis.index'))->assertForbidden();
});
