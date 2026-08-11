<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\AngsuranPinjaman;
use App\Models\Desa;
use App\Models\Pinjaman;
use App\Models\SektorUsaha;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Seeder untuk Pinjaman dan Angsuran
 *
 * Membuat data pinjaman dan angsuran untuk Desa Kelapapati
 * Periode: Desember 2025 dan Januari 2026
 */
class PinjamanAngsuranSeeder extends Seeder
{
    protected $desa;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get Desa Kelapapati
        $this->desa = Desa::where('nama_desa', 'Desa Kelapapati')
            ->orWhere('kode_desa', 'DES005')
            ->first();

        if (! $this->desa) {
            $this->command->error('Desa Kelapapati tidak ditemukan. Jalankan DesaSeeder terlebih dahulu.');

            return;
        }

        $this->command->info("✓ Menggunakan Desa: {$this->desa->nama_desa} (ID: {$this->desa->id})");

        DB::transaction(function () {
            // Get anggota
            $anggota = Anggota::where('desa_id', $this->desa->id)
                ->where('status', 'aktif')
                ->limit(5)
                ->get();

            if ($anggota->isEmpty()) {
                $this->command->warn('⚠ Tidak ada anggota untuk membuat pinjaman. Jalankan TestingDataSeeder terlebih dahulu.');

                return;
            }

            // Sektor usaha (opsional): ambil satu untuk desa ini
            $sektorUsahaId = SektorUsaha::where('desa_id', $this->desa->id)->aktif()->inRandomOrder()->value('id');

            // 1. Create Pinjaman untuk Desember 2025
            $this->createPinjamanDesember2025($anggota, $sektorUsahaId);

            // 2. Create Pinjaman untuk Januari 2026
            $this->createPinjamanJanuari2026($anggota, $sektorUsahaId);

            // 3. Create Angsuran untuk pinjaman yang sudah ada
            $this->createAngsuran();
        });

        $this->command->info('✓ Pinjaman dan Angsuran berhasil dibuat!');
    }

    /**
     * Create Pinjaman untuk Desember 2025
     *
     * @param  Collection  $anggota
     */
    protected function createPinjamanDesember2025($anggota, ?int $sektorUsahaId = null): void
    {
        $pinjamanData = [
            [
                'anggota' => $anggota[0],
                'tanggal' => '2025-12-01',
                'jumlah' => 5000000,
                'jangka_waktu' => 6,
                'jasa_persen' => 2.5,
            ],
            [
                'anggota' => $anggota[1],
                'tanggal' => '2025-12-05',
                'jumlah' => 3000000,
                'jangka_waktu' => 4,
                'jasa_persen' => 2.0,
            ],
            [
                'anggota' => $anggota[2],
                'tanggal' => '2025-12-10',
                'jumlah' => 4000000,
                'jangka_waktu' => 5,
                'jasa_persen' => 2.5,
            ],
        ];

        foreach ($pinjamanData as $index => $data) {
            $nomorPinjaman = 'PNJ/'.Carbon::parse($data['tanggal'])->format('Y/m').'/'.str_pad($index + 1, 5, '0', STR_PAD_LEFT);

            $attrs = [
                'anggota_id' => $data['anggota']->id,
                'tanggal_pinjaman' => $data['tanggal'],
                'jumlah_pinjaman' => $data['jumlah'],
                'jangka_waktu_bulan' => $data['jangka_waktu'],
                'jasa_persen' => $data['jasa_persen'],
                'status_pinjaman' => 'aktif',
            ];
            if ($sektorUsahaId !== null) {
                $attrs['sektor_usaha_id'] = $sektorUsahaId;
            }
            Pinjaman::firstOrCreate(
                [
                    'desa_id' => $this->desa->id,
                    'nomor_pinjaman' => $nomorPinjaman,
                ],
                $attrs
            );
        }

        $this->command->info('✓ Pinjaman Desember 2025 berhasil dibuat (3 pinjaman)');
    }

    /**
     * Create Pinjaman untuk Januari 2026
     *
     * @param  Collection  $anggota
     */
    protected function createPinjamanJanuari2026($anggota, ?int $sektorUsahaId = null): void
    {
        $pinjamanData = [
            [
                'anggota' => $anggota[3],
                'tanggal' => '2026-01-02',
                'jumlah' => 6000000,
                'jangka_waktu' => 6,
                'jasa_persen' => 2.5,
            ],
            [
                'anggota' => $anggota[4],
                'tanggal' => '2026-01-08',
                'jumlah' => 3500000,
                'jangka_waktu' => 4,
                'jasa_persen' => 2.0,
            ],
        ];

        foreach ($pinjamanData as $index => $data) {
            $nomorPinjaman = 'PNJ/'.Carbon::parse($data['tanggal'])->format('Y/m').'/'.str_pad($index + 1, 5, '0', STR_PAD_LEFT);

            $attrs = [
                'anggota_id' => $data['anggota']->id,
                'tanggal_pinjaman' => $data['tanggal'],
                'jumlah_pinjaman' => $data['jumlah'],
                'jangka_waktu_bulan' => $data['jangka_waktu'],
                'jasa_persen' => $data['jasa_persen'],
                'status_pinjaman' => 'aktif',
            ];
            if ($sektorUsahaId !== null) {
                $attrs['sektor_usaha_id'] = $sektorUsahaId;
            }
            Pinjaman::firstOrCreate(
                [
                    'desa_id' => $this->desa->id,
                    'nomor_pinjaman' => $nomorPinjaman,
                ],
                $attrs
            );
        }

        $this->command->info('✓ Pinjaman Januari 2026 berhasil dibuat (2 pinjaman)');
    }

    /**
     * Create Angsuran untuk pinjaman yang sudah ada
     */
    protected function createAngsuran(): void
    {
        $pinjaman = Pinjaman::where('desa_id', $this->desa->id)
            ->where('status_pinjaman', 'aktif')
            ->get();

        foreach ($pinjaman as $p) {
            $jumlahPinjaman = $p->jumlah_pinjaman;
            $jangkaWaktu = $p->jangka_waktu_bulan;
            $jasaPersen = $p->jasa_persen;

            // Hitung angsuran per bulan
            $pokokPerBulan = $jumlahPinjaman / $jangkaWaktu;
            $jasaPerBulan = $jumlahPinjaman * ($jasaPersen / 100);
            $totalPerBulan = $pokokPerBulan + $jasaPerBulan;

            // Buat angsuran untuk bulan pertama (Desember 2025 atau Januari 2026)
            $tanggalPinjaman = Carbon::parse($p->tanggal_pinjaman);
            $tanggalAngsuran = $tanggalPinjaman->copy()->addMonth();

            // Hanya buat angsuran jika tanggal angsuran <= Januari 2026
            if ($tanggalAngsuran->format('Y-m') <= '2026-01') {
                AngsuranPinjaman::firstOrCreate(
                    [
                        'pinjaman_id' => $p->id,
                        'angsuran_ke' => 1,
                    ],
                    [
                        'tanggal_bayar' => $tanggalAngsuran->format('Y-m-d'),
                        'pokok_dibayar' => $pokokPerBulan,
                        'jasa_dibayar' => $jasaPerBulan,
                        'denda_dibayar' => 0,
                        'total_dibayar' => $totalPerBulan,
                    ]);
            }
        }

        $this->command->info('✓ Angsuran berhasil dibuat');
    }
}
