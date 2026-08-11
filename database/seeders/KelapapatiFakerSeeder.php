<?php

namespace Database\Seeders;

use App\Models\Akun;
use App\Models\Anggota;
use App\Models\AngsuranPinjaman;
use App\Models\Desa;
use App\Models\Kelompok;
use App\Models\Pinjaman;
use App\Models\SektorUsaha;
use App\Models\TransaksiKas;
use App\Models\UnitUsaha;
use App\Models\User;
use App\Services\AccountingService;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Seeder data faker/dummy untuk Desa Kelapapati
 *
 * Membuat: Kelompok → Anggota → Transaksi Kas (+ Jurnal) → Pinjaman → Angsuran
 * Jalankan setelah: KecamatanSeeder, DesaSeeder, UserSeeder, GlobalCoaSeeder
 * Bisa dijalankan setelah TestingDataSeeder (akan menambah data) atau standalone.
 */
class KelapapatiFakerSeeder extends Seeder
{
    protected $desa;

    protected $unitUsahaUSP;

    protected $unitUsahaUMUM;

    protected $accountingService;

    protected $faker;

    /** Jumlah data dummy (bisa di-override dari env atau default) */
    protected int $jumlahKelompok = 5;

    protected int $jumlahAnggota = 40;

    protected int $jumlahTransaksiKas = 35;

    protected int $jumlahPinjaman = 6;

    public function run(): void
    {
        $this->faker = Factory::create('id_ID');
        $this->accountingService = app(AccountingService::class);

        $this->desa = Desa::where('nama_desa', 'Desa Kelapapati')
            ->orWhere('kode_desa', 'DES005')
            ->first();

        if (! $this->desa) {
            $this->command->error('Desa Kelapapati tidak ditemukan. Jalankan DesaSeeder terlebih dahulu.');

            return;
        }

        $this->command->info("Menggunakan Desa: {$this->desa->nama_desa} (ID: {$this->desa->id})");

        DB::transaction(function () {
            $this->ensureUnitUsaha();
            $kelompokIds = $this->createKelompok();
            $anggotaIds = $this->createAnggota($kelompokIds);
            $this->createTransaksiKas();
            $this->createPinjamanDanAngsuran($anggotaIds);
            $this->postNeracaSaldo();
        });

        $this->command->info('Seeder faker Desa Kelapapati selesai.');
    }

    protected function ensureUnitUsaha(): void
    {
        $user = User::where('desa_id', $this->desa->id)->first() ?? User::first();

        $this->unitUsahaUSP = UnitUsaha::firstOrCreate(
            [
                'desa_id' => $this->desa->id,
                'kode_unit' => 'USP',
            ],
            [
                'nama_unit' => 'Unit Simpan Pinjam',
                'status' => 'aktif',
                'created_by' => $user?->id,
            ]
        );

        $this->unitUsahaUMUM = UnitUsaha::firstOrCreate(
            [
                'desa_id' => $this->desa->id,
                'kode_unit' => 'UMUM',
            ],
            [
                'nama_unit' => 'Unit Usaha Umum',
                'status' => 'aktif',
                'created_by' => $user?->id,
            ]
        );

        $this->command->info('Unit Usaha siap.');
    }

    /**
     * @return array<int, int> kelompok_id => id
     */
    protected function createKelompok(): array
    {
        $namaKelompok = [
            'Kelompok Melati',
            'Kelompok Anggrek',
            'Kelompok Mawar',
            'Kelompok Kenanga',
            'Kelompok Dahlia',
        ];

        $ids = [];
        $max = min($this->jumlahKelompok, count($namaKelompok));

        for ($i = 0; $i < $max; $i++) {
            $nama = $namaKelompok[$i];
            $k = Kelompok::firstOrCreate(
                [
                    'desa_id' => $this->desa->id,
                    'nama_kelompok' => $nama,
                ],
                [
                    'keterangan' => 'Kelompok binaan '.$this->desa->nama_desa,
                    'status' => 'aktif',
                ]
            );
            $ids[] = $k->id;
        }

        $this->command->info("Kelompok: {$max} kelompok.");

        return $ids;
    }

    /**
     * @param  array<int, int>  $kelompokIds
     * @return Collection<int, int> anggota ids
     */
    protected function createAnggota(array $kelompokIds): Collection
    {
        if (empty($kelompokIds)) {
            $this->command->warn('Tidak ada kelompok, skip anggota.');

            return collect();
        }

        $anggotaIds = collect();
        $nikUsed = Anggota::where('desa_id', $this->desa->id)->pluck('nik')->flip()->all();

        for ($i = 0; $i < $this->jumlahAnggota; $i++) {
            $nik = $this->generateUniqueNik($nikUsed);
            $nikUsed[$nik] = true;

            $kelompokId = $kelompokIds[array_rand($kelompokIds)];
            $jenisKelamin = $this->faker->randomElement(['L', 'P']);
            $tanggalGabung = $this->faker->dateTimeBetween('-2 years', 'now');

            $a = Anggota::create([
                'desa_id' => $this->desa->id,
                'kelompok_id' => $kelompokId,
                'nama' => $this->faker->name(),
                'nik' => $nik,
                'alamat' => $this->faker->streetAddress().', RT '.$this->faker->numberBetween(1, 10).'/RW '.$this->faker->numberBetween(1, 5),
                'nomor_hp' => '08'.$this->faker->numerify('##########'),
                'jenis_kelamin' => $jenisKelamin,
                'tanggal_gabung' => Carbon::instance($tanggalGabung),
                'status' => 'aktif',
            ]);
            $anggotaIds->push($a->id);
        }

        $this->command->info("Anggota: {$this->jumlahAnggota} anggota.");

        return $anggotaIds;
    }

    protected function generateUniqueNik(array &$used): string
    {
        do {
            $nik = '14'.$this->faker->numerify('##############');
        } while (isset($used[$nik]));

        return $nik;
    }

    protected function createTransaksiKas(): void
    {
        $akunKas = Akun::aktif()->where('nama_akun', 'Kas')->first();
        $akunPendapatan = Akun::aktif()->whereIn('nama_akun', ['Pendapatan Simpanan', 'Pendapatan Jasa Pinjaman'])->get();
        $akunBeban = Akun::aktif()->whereIn('nama_akun', ['Beban Operasional', 'Beban Gaji', 'Beban Administrasi'])->get();

        if (! $akunKas || $akunPendapatan->isEmpty() || $akunBeban->isEmpty()) {
            $this->command->warn('Akun tidak lengkap, skip transaksi kas.');

            return;
        }

        $uraianMasuk = [
            'Setoran simpanan wajib anggota',
            'Setoran simpanan sukarela',
            'Pendapatan jasa pinjaman',
            'Pendapatan simpanan anggota',
            'Setoran angsuran pinjaman',
        ];
        $uraianKeluar = [
            'Beban operasional kantor',
            'Beban gaji karyawan',
            'Beban administrasi',
            'Pembelian ATK',
        ];

        $count = 0;
        $start = Carbon::parse('2025-11-01');
        $end = Carbon::parse('2026-01-31');

        for ($i = 0; $i < $this->jumlahTransaksiKas; $i++) {
            $tanggal = Carbon::instance($this->faker->dateTimeBetween($start, $end));
            $jenis = $this->faker->randomElement(['masuk', 'keluar']);
            $jumlah = (int) $this->faker->randomElement([500000, 1000000, 1500000, 2000000, 2500000, 3000000, 4000000, 5000000]);

            if ($jenis === 'masuk') {
                $akunLawan = $akunPendapatan->random();
                $uraian = $this->faker->randomElement($uraianMasuk);
            } else {
                $akunLawan = $akunBeban->random();
                $uraian = $this->faker->randomElement($uraianKeluar);
            }

            $transaksiKas = TransaksiKas::create([
                'desa_id' => $this->desa->id,
                'unit_usaha_id' => $this->unitUsahaUSP->id,
                'tanggal_transaksi' => $tanggal,
                'jenis_transaksi' => $jenis,
                'akun_kas_id' => $akunKas->id,
                'akun_lawan_id' => $akunLawan->id,
                'jumlah' => $jumlah,
                'uraian' => $uraian,
            ]);

            $details = $jenis === 'masuk'
                ? [
                    ['akun_id' => $akunKas->id, 'posisi' => 'debit', 'jumlah' => $jumlah, 'keterangan' => $uraian],
                    ['akun_id' => $akunLawan->id, 'posisi' => 'kredit', 'jumlah' => $jumlah, 'keterangan' => $uraian],
                ]
                : [
                    ['akun_id' => $akunLawan->id, 'posisi' => 'debit', 'jumlah' => $jumlah, 'keterangan' => $uraian],
                    ['akun_id' => $akunKas->id, 'posisi' => 'kredit', 'jumlah' => $jumlah, 'keterangan' => $uraian],
                ];

            $this->accountingService->createJurnal([
                'desa_id' => $this->desa->id,
                'unit_usaha_id' => $this->unitUsahaUSP->id,
                'tanggal_transaksi' => $tanggal->format('Y-m-d'),
                'jenis_jurnal' => 'kas_harian',
                'keterangan' => $uraian,
                'status' => 'posted',
                'transaksi_kas_id' => $transaksiKas->id,
                'details' => $details,
            ]);
            $count++;
        }

        $this->command->info("Transaksi Kas (+ jurnal): {$count} transaksi.");
    }

    /**
     * @param  Collection<int, int>  $anggotaIds
     */
    protected function createPinjamanDanAngsuran(Collection $anggotaIds): void
    {
        if ($anggotaIds->isEmpty() || $this->jumlahPinjaman <= 0) {
            return;
        }

        $anggotas = Anggota::whereIn('id', $anggotaIds->random(min($this->jumlahPinjaman, $anggotaIds->count())))->get();
        if ($anggotas->isEmpty()) {
            return;
        }

        $sektorIds = SektorUsaha::where('desa_id', $this->desa->id)->aktif()->pluck('id')->all();

        $bulan = [Carbon::parse('2025-11-01'), Carbon::parse('2025-12-01'), Carbon::parse('2026-01-01')];
        $nomorUrut = 1;

        foreach ($anggotas as $anggota) {
            $tanggal = $this->faker->randomElement($bulan)->copy()->addDays($this->faker->numberBetween(1, 20));
            $nomorPinjaman = 'PNJ/'.$tanggal->format('Y/m').'/'.str_pad($nomorUrut++, 5, '0', STR_PAD_LEFT);

            $attrs = [
                'anggota_id' => $anggota->id,
                'tanggal_pinjaman' => $tanggal,
                'jumlah_pinjaman' => (int) $this->faker->randomElement([3000000, 4000000, 5000000, 6000000, 8000000]),
                'jangka_waktu_bulan' => $this->faker->randomElement([4, 6, 8, 10]),
                'jasa_persen' => $this->faker->randomElement([2.0, 2.5, 3.0]),
                'status_pinjaman' => 'aktif',
            ];
            if (! empty($sektorIds)) {
                $attrs['sektor_usaha_id'] = $this->faker->randomElement($sektorIds);
            }

            $pinjaman = Pinjaman::firstOrCreate(
                [
                    'desa_id' => $this->desa->id,
                    'nomor_pinjaman' => $nomorPinjaman,
                ],
                $attrs
            );

            // Beberapa angsuran untuk pinjaman ini
            $jumlahAngsuran = $this->faker->numberBetween(0, min(3, $pinjaman->jangka_waktu_bulan));
            for ($k = 1; $k <= $jumlahAngsuran; $k++) {
                $tanggalBayar = $tanggal->copy()->addMonths($k);
                $pokok = (int) round($pinjaman->jumlah_pinjaman / $pinjaman->jangka_waktu_bulan);
                $jasa = (int) round($pinjaman->jumlah_pinjaman * ($pinjaman->jasa_persen / 100) / $pinjaman->jangka_waktu_bulan);
                $denda = 0;
                $total = $pokok + $jasa + $denda;

                AngsuranPinjaman::firstOrCreate(
                    [
                        'pinjaman_id' => $pinjaman->id,
                        'angsuran_ke' => $k,
                    ],
                    [
                        'tanggal_bayar' => $tanggalBayar,
                        'pokok_dibayar' => $pokok,
                        'jasa_dibayar' => $jasa,
                        'denda_dibayar' => $denda,
                        'total_dibayar' => $total,
                    ]
                );
            }
        }

        $this->command->info("Pinjaman & Angsuran: {$anggotas->count()} pinjaman (+ angsuran).");
    }

    protected function postNeracaSaldo(): void
    {
        try {
            foreach (['2025-11', '2025-12', '2026-01'] as $periode) {
                $this->accountingService->recalculateBalance($this->desa->id, $periode);
            }
            $this->command->info('Neraca saldo diposting.');
        } catch (\Throwable $e) {
            $this->command->warn('Post neraca saldo: '.$e->getMessage());
        }
    }
}
