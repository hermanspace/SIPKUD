<?php

use App\Models\Akun;
use App\Models\Anggota;
use App\Models\Desa;
use App\Models\Kelompok;
use App\Models\NeracaSaldo;
use App\Models\Pinjaman;
use App\Models\TransaksiKas;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\KolektibilitasService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->desa = Desa::factory()->create();
    $this->user = User::factory()->adminDesa($this->desa->id, $this->desa->kecamatan_id)->create();
    $this->actingAs($this->user);

    $this->akunKas = Akun::factory()->tipe('aset')->create(['kode_akun' => '1-1000', 'nama_akun' => 'Kas']);
    $this->akunPendapatan = Akun::factory()->tipe('pendapatan')->create(['nama_akun' => 'Pendapatan Jasa Pinjaman (Bunga)']);
    $this->akunBeban = Akun::factory()->tipe('beban')->create(['nama_akun' => 'Beban Gaji dan Upah']);

    $this->service = app(AccountingService::class);
});

function jurnalStandar(int $desaId, int $akunDebit, int $akunKredit, float $jumlah, string $tanggal): void
{
    test()->service->createJurnal([
        'desa_id' => $desaId,
        'tanggal_transaksi' => $tanggal,
        'jenis_jurnal' => 'memorial',
        'keterangan' => 'Transaksi uji standar',
        'status' => 'posted',
        'details' => [
            ['akun_id' => $akunDebit, 'posisi' => 'debit', 'jumlah' => $jumlah],
            ['akun_id' => $akunKredit, 'posisi' => 'kredit', 'jumlah' => $jumlah],
        ],
    ]);
}

// ---------------------------------------------------------------------------
// Laporan Arus Kas
// ---------------------------------------------------------------------------

it('menyusun laporan arus kas metode langsung dengan klasifikasi aktivitas', function () {
    $akunModal = Akun::factory()->tipe('ekuitas')->create(['kode_akun' => '3-1000', 'nama_akun' => 'Modal Penyertaan Desa']);
    $akunGedung = Akun::factory()->tipe('aset')->create(['kode_akun' => '1-1400', 'nama_akun' => 'Gedung/Bangunan']);

    $buat = fn (string $jenis, int $lawan, float $jumlah) => TransaksiKas::create([
        'desa_id' => $this->desa->id,
        'tanggal_transaksi' => '2026-03-10',
        'uraian' => 'uji',
        'jenis_transaksi' => $jenis,
        'akun_kas_id' => $this->akunKas->id,
        'akun_lawan_id' => $lawan,
        'jumlah' => $jumlah,
    ]);

    $buat('masuk', $this->akunPendapatan->id, 500000);  // operasi
    $buat('keluar', $this->akunBeban->id, 200000);      // operasi
    $buat('masuk', $akunModal->id, 1000000);            // pendanaan
    $buat('keluar', $akunGedung->id, 300000);           // investasi (prefix 1-14)

    $arusKas = $this->service->getArusKas($this->desa->id, 2026, 3);

    expect($arusKas['aktivitas']['operasi']['neto'])->toBe(300000.0)
        ->and($arusKas['aktivitas']['investasi']['neto'])->toBe(-300000.0)
        ->and($arusKas['aktivitas']['pendanaan']['neto'])->toBe(1000000.0)
        ->and($arusKas['kenaikan_kas'])->toBe(1000000.0)
        ->and($arusKas['saldo_akhir_kas'])->toBe(1000000.0);
});

it('menampilkan halaman laporan arus kas dan perubahan ekuitas', function () {
    $this->get(route('laporan.arus-kas'))->assertOk();
    $this->get(route('laporan.perubahan-ekuitas'))->assertOk();
});

// ---------------------------------------------------------------------------
// Tutup Buku Tahunan
// ---------------------------------------------------------------------------

it('menutup tahun buku: pendapatan & beban ditutup ke SHU dan direklasifikasi', function () {
    $akunShuBerjalan = Akun::factory()->tipe('ekuitas')->create(['nama_akun' => 'SHU Tahun Berjalan']);
    $akunShuTahunLalu = Akun::factory()->tipe('ekuitas')->create(['nama_akun' => 'SHU Tahun Lalu']);

    jurnalStandar($this->desa->id, $this->akunKas->id, $this->akunPendapatan->id, 800000, '2026-05-10');
    jurnalStandar($this->desa->id, $this->akunBeban->id, $this->akunKas->id, 300000, '2026-06-10');

    $result = $this->service->closeYear($this->desa->id, 2026);

    expect($result['laba_bersih'])->toBe(500000.0)
        ->and($result['jurnal_penutup']->jenis_jurnal)->toBe('penutup')
        ->and($this->service->isYearClosed($this->desa->id, 2026))->toBeTrue();

    // SHU Tahun Berjalan per Des = laba; setelah reklas 1 Jan, SHU Tahun Lalu terisi
    $saldoShuLalu = NeracaSaldo::withoutGlobalScopes()
        ->where('desa_id', $this->desa->id)
        ->where('akun_id', $akunShuTahunLalu->id)
        ->where('periode', '2027-01')
        ->first();

    expect((float) $saldoShuLalu->saldo_akhir_kredit - (float) $saldoShuLalu->saldo_akhir_debit)->toBe(500000.0);

    // Tutup dua kali harus ditolak
    expect(fn () => $this->service->closeYear($this->desa->id, 2026))
        ->toThrow(ValidationException::class);
});

// ---------------------------------------------------------------------------
// Kolektibilitas & Penyisihan
// ---------------------------------------------------------------------------

function pinjamanUji(Desa $desa, string $tanggal, int $tenor = 12): Pinjaman
{
    $kelompok = Kelompok::create(['desa_id' => $desa->id, 'nama_kelompok' => 'K-'.uniqid(), 'status' => 'aktif']);
    $anggota = Anggota::create([
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
        'nama' => 'Anggota '.uniqid(),
        'nik' => (string) random_int(1000000000000000, 9999999999999999),
        'tanggal_gabung' => now()->toDateString(),
        'status' => 'aktif',
    ]);

    return Pinjaman::withoutEvents(fn () => Pinjaman::create([
        'desa_id' => $desa->id,
        'anggota_id' => $anggota->id,
        'nomor_pinjaman' => 'PJ-'.uniqid(),
        'tanggal_pinjaman' => $tanggal,
        'jumlah_pinjaman' => 1000000,
        'jangka_waktu_bulan' => $tenor,
        'jasa_persen' => 2,
        'status_pinjaman' => 'aktif',
    ]));
}

it('mengklasifikasikan kolektibilitas berdasar umur tunggakan', function () {
    $lancar = pinjamanUji($this->desa, now()->toDateString());
    $macet = pinjamanUji($this->desa, now()->subMonths(10)->toDateString());

    expect($lancar->kolektibilitas)->toBe('lancar')
        ->and($macet->tunggakan_bulan)->toBeGreaterThanOrEqual(7)
        ->and($macet->kolektibilitas)->toBe('macet');
});

it('membuat jurnal penyisihan piutang sesuai target kolektibilitas', function () {
    Akun::factory()->tipe('beban')->create(['nama_akun' => 'Beban Penyisihan Kerugian Piutang']);
    Akun::factory()->tipe('aset')->create(['nama_akun' => 'Cadangan Kerugian Piutang']);

    // Pinjaman macet 1 juta -> penyisihan 100%
    pinjamanUji($this->desa, now()->subMonths(10)->toDateString());

    $service = app(KolektibilitasService::class);
    $result = $service->buatJurnalPenyisihan($this->desa->id);

    expect($result['penyesuaian'])->toBe(1000000.0)
        ->and($result['jurnal'])->not->toBeNull();

    // Dipanggil kedua kali: sudah sesuai target, tidak ada jurnal baru
    $kedua = $service->buatJurnalPenyisihan($this->desa->id);
    expect($kedua['jurnal'])->toBeNull();
});

it('menampilkan halaman kolektibilitas dan CALK', function () {
    $this->get(route('laporan.kolektibilitas'))->assertOk();
    $this->get(route('laporan.calk'))->assertOk();
    $this->get(route('periode.tutup-tahun'))->assertOk();
});
