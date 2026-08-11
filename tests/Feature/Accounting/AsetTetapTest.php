<?php

use App\Models\Akun;
use App\Models\AsetTetap;
use App\Models\Desa;
use App\Models\NeracaSaldo;
use App\Models\User;
use App\Services\AsetTetapService;

beforeEach(function () {
    $this->desa = Desa::factory()->create();
    $this->user = User::factory()->adminDesa($this->desa->id, $this->desa->kecamatan_id)->create();
    $this->actingAs($this->user);

    $this->akunPeralatan = Akun::factory()->tipe('aset')->create(['kode_akun' => '1-1300', 'nama_akun' => 'Peralatan Kantor']);
    $this->akunAkumulasi = Akun::factory()->tipe('aset')->create(['kode_akun' => '1-1310', 'nama_akun' => 'Akumulasi Penyusutan Peralatan']);
    $this->akunBeban = Akun::factory()->tipe('beban')->create(['nama_akun' => 'Beban Penyusutan Peralatan']);
});

function asetUji(Desa $desa, array $override = []): AsetTetap
{
    return AsetTetap::create(array_merge([
        'desa_id' => $desa->id,
        'nama_aset' => 'Laptop Uji',
        'tanggal_perolehan' => now()->subMonths(2)->toDateString(),
        'harga_perolehan' => 12000000,
        'nilai_residu' => 0,
        'umur_bulan' => 48,
        'akun_aset_id' => test()->akunPeralatan->id,
        'akun_akumulasi_id' => test()->akunAkumulasi->id,
        'akun_beban_id' => test()->akunBeban->id,
        'status' => 'aktif',
    ], $override));
}

it('menghitung penyusutan garis lurus per bulan', function () {
    $aset = asetUji($this->desa, ['harga_perolehan' => 12000000, 'nilai_residu' => 2400000, 'umur_bulan' => 48]);

    expect($aset->penyusutan_bulanan)->toBe(200000.0)
        ->and($aset->nilai_buku)->toBe(12000000.0);
});

it('memproses penyusutan bulanan: jurnal dibuat dan idempoten', function () {
    asetUji($this->desa); // 12jt / 48 bln = 250rb per bulan

    $service = app(AsetTetapService::class);
    $hasil = $service->prosesPenyusutan($this->desa->id);

    expect($hasil['diproses'])->toBe(1)
        ->and($hasil['total'])->toBe(250000.0);

    // Ledger: akumulasi penyusutan bertambah di sisi kredit
    $ledger = NeracaSaldo::withoutGlobalScopes()
        ->where('desa_id', $this->desa->id)
        ->where('akun_id', $this->akunAkumulasi->id)
        ->where('periode', now()->format('Y-m'))
        ->first();
    expect((float) $ledger->mutasi_kredit)->toBe(250000.0);

    // Idempoten: proses ulang periode sama = tidak ada yang diproses
    expect($service->prosesPenyusutan($this->desa->id)['diproses'])->toBe(0);
});

it('berhenti menyusutkan saat nilai habis', function () {
    $aset = asetUji($this->desa, [
        'harga_perolehan' => 1000000,
        'umur_bulan' => 10,
        'akumulasi_tercatat' => 950000, // sisa 50rb < penyusutan bulanan 100rb
    ]);

    $hasil = app(AsetTetapService::class)->prosesPenyusutan($this->desa->id);

    expect($hasil['total'])->toBe(50000.0)
        ->and($aset->fresh()->sisa_disusutkan)->toBe(0.0);

    // Bulan berikutnya: tidak ada lagi yang disusutkan
    $lagi = app(AsetTetapService::class)->prosesPenyusutan($this->desa->id, now()->addMonth()->format('Y-m'));
    expect($lagi['diproses'])->toBe(0);
});

it('menampilkan halaman aset tetap dan laporan tahunan', function () {
    $this->get(route('aset-tetap.index'))->assertOk();
    $this->get(route('laporan.tahunan'))->assertOk();
});

it('menjalankan command aset:penyusutan untuk semua desa', function () {
    asetUji($this->desa);

    $this->artisan('aset:penyusutan')->assertExitCode(0);

    expect(asetUji($this->desa)->fresh()->periode_penyusutan_terakhir)->toBeNull(); // aset baru belum diproses
    expect(AsetTetap::withoutGlobalScopes()->whereNotNull('periode_penyusutan_terakhir')->count())->toBe(1);
});
