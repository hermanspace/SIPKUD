<?php

namespace Database\Seeders;

use App\Models\Pengumuman;
use App\Models\User;
use Illuminate\Database\Seeder;

class PengumumanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::where('role', 'super_admin')->first();

        if (! $superAdmin) {
            $this->command->warn('Super Admin tidak ditemukan. Skip seeding pengumuman.');

            return;
        }

        $pengumuman = [
            [
                'judul' => 'Selamat Datang di SIPKUD',
                'isi' => 'Sistem Informasi Pelaporan Keuangan USP Desa telah aktif. Silakan gunakan sistem ini untuk mengelola data keuangan USP/UED-SP di desa Anda.',
                'prioritas' => 'tinggi',
                'tipe' => 'info',
                'aktif' => true,
                'tanggal_mulai' => now(),
                'tanggal_selesai' => now()->addMonths(3),
                'created_by' => $superAdmin->id,
            ],
            [
                'judul' => 'Pelaporan Bulanan Wajib Dilakukan',
                'isi' => 'Mengingatkan kepada seluruh Admin Desa untuk melakukan pelaporan keuangan bulanan paling lambat tanggal 5 setiap bulannya. Pastikan data pinjaman dan angsuran sudah tercatat dengan benar.',
                'prioritas' => 'tinggi',
                'tipe' => 'peringatan',
                'aktif' => true,
                'tanggal_mulai' => now(),
                'tanggal_selesai' => null,
                'created_by' => $superAdmin->id,
            ],
            [
                'judul' => 'Pemeliharaan Sistem Terjadwal',
                'isi' => 'Sistem akan menjalani pemeliharaan rutin setiap hari Minggu pukul 00:00 - 02:00 WIB. Mohon tidak melakukan transaksi pada waktu tersebut.',
                'prioritas' => 'sedang',
                'tipe' => 'info',
                'aktif' => true,
                'tanggal_mulai' => now(),
                'tanggal_selesai' => null,
                'created_by' => $superAdmin->id,
            ],
            [
                'judul' => 'Update Fitur Export Laporan',
                'isi' => 'Fitur export laporan ke Excel dan PDF telah tersedia. Anda dapat mengexport data dari menu Laporan, Master Pinjaman, Master Angsuran, dan Master Anggota.',
                'prioritas' => 'sedang',
                'tipe' => 'info',
                'aktif' => true,
                'tanggal_mulai' => now()->subDays(7),
                'tanggal_selesai' => now()->addDays(7),
                'created_by' => $superAdmin->id,
            ],
            [
                'judul' => 'Perhatian: Validasi Data NIK',
                'isi' => 'Pastikan NIK anggota yang diinput sudah benar dan sesuai dengan KTP. NIK yang salah dapat menyebabkan masalah dalam pelaporan.',
                'prioritas' => 'tinggi',
                'tipe' => 'penting',
                'aktif' => true,
                'tanggal_mulai' => now(),
                'tanggal_selesai' => null,
                'created_by' => $superAdmin->id,
            ],
        ];

        foreach ($pengumuman as $data) {
            Pengumuman::firstOrCreate(
                ['judul' => $data['judul']],
                $data
            );
        }

        $this->command->info('✅ Seeder pengumuman berhasil dijalankan. Total: '.count($pengumuman).' pengumuman.');
    }
}
