<?php

namespace Database\Seeders;

use App\Models\Akun;
use Illuminate\Database\Seeder;

/**
 * Seeder Chart of Accounts (COA) standar BUM Desa - extended.
 * Akun global (tanpa desa_id), sama untuk seluruh desa.
 * Untuk COA minimal gunakan GlobalCoaSeeder.
 */
class AkunSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Chart of Accounts standar untuk BUM Desa
        $chartOfAccounts = [
            // ============================================
            // 1. ASET (Assets)
            // ============================================
            [
                'kode_akun' => '1-1000',
                'nama_akun' => 'Kas',
                'tipe_akun' => 'aset',
            ],
            [
                'kode_akun' => '1-1010',
                'nama_akun' => 'Bank',
                'tipe_akun' => 'aset',
            ],
            [
                'kode_akun' => '1-1020',
                'nama_akun' => 'Kas Kecil',
                'tipe_akun' => 'aset',
            ],
            [
                'kode_akun' => '1-1100',
                'nama_akun' => 'Piutang Pinjaman Anggota',
                'tipe_akun' => 'aset',
            ],
            [
                'kode_akun' => '1-1110',
                'nama_akun' => 'Cadangan Kerugian Piutang',
                'tipe_akun' => 'aset',
            ],
            [
                'kode_akun' => '1-1200',
                'nama_akun' => 'Perlengkapan Kantor',
                'tipe_akun' => 'aset',
            ],
            [
                'kode_akun' => '1-1300',
                'nama_akun' => 'Peralatan Kantor',
                'tipe_akun' => 'aset',
            ],
            [
                'kode_akun' => '1-1310',
                'nama_akun' => 'Akumulasi Penyusutan Peralatan',
                'tipe_akun' => 'aset',
            ],
            [
                'kode_akun' => '1-1400',
                'nama_akun' => 'Gedung/Bangunan',
                'tipe_akun' => 'aset',
            ],
            [
                'kode_akun' => '1-1410',
                'nama_akun' => 'Akumulasi Penyusutan Gedung',
                'tipe_akun' => 'aset',
            ],

            // ============================================
            // 2. KEWAJIBAN (Liabilities)
            // ============================================
            [
                'kode_akun' => '2-1000',
                'nama_akun' => 'Simpanan Anggota - Pokok',
                'tipe_akun' => 'kewajiban',
            ],
            [
                'kode_akun' => '2-1010',
                'nama_akun' => 'Simpanan Anggota - Wajib',
                'tipe_akun' => 'kewajiban',
            ],
            [
                'kode_akun' => '2-1020',
                'nama_akun' => 'Simpanan Anggota - Sukarela',
                'tipe_akun' => 'kewajiban',
            ],
            [
                'kode_akun' => '2-1100',
                'nama_akun' => 'Utang Bank',
                'tipe_akun' => 'kewajiban',
            ],
            [
                'kode_akun' => '2-1200',
                'nama_akun' => 'Utang Bunga',
                'tipe_akun' => 'kewajiban',
            ],
            [
                'kode_akun' => '2-1300',
                'nama_akun' => 'Utang Pajak',
                'tipe_akun' => 'kewajiban',
            ],

            // ============================================
            // 3. EKUITAS/MODAL (Equity)
            // ============================================
            [
                'kode_akun' => '3-1000',
                'nama_akun' => 'Modal Penyertaan Desa',
                'tipe_akun' => 'ekuitas',
            ],
            [
                'kode_akun' => '3-1100',
                'nama_akun' => 'Modal Penyertaan Masyarakat',
                'tipe_akun' => 'ekuitas',
            ],
            [
                'kode_akun' => '3-2000',
                'nama_akun' => 'Cadangan Umum',
                'tipe_akun' => 'ekuitas',
            ],
            [
                'kode_akun' => '3-2100',
                'nama_akun' => 'Cadangan Tujuan',
                'tipe_akun' => 'ekuitas',
            ],
            [
                'kode_akun' => '3-3000',
                'nama_akun' => 'SHU Tahun Berjalan',
                'tipe_akun' => 'ekuitas',
            ],
            [
                'kode_akun' => '3-3100',
                'nama_akun' => 'SHU Tahun Lalu',
                'tipe_akun' => 'ekuitas',
            ],

            // ============================================
            // 4. PENDAPATAN (Income/Revenue)
            // ============================================
            [
                'kode_akun' => '4-1000',
                'nama_akun' => 'Pendapatan Jasa Pinjaman (Bunga)',
                'tipe_akun' => 'pendapatan',
            ],
            [
                'kode_akun' => '4-1100',
                'nama_akun' => 'Pendapatan Administrasi Pinjaman',
                'tipe_akun' => 'pendapatan',
            ],
            [
                'kode_akun' => '4-1200',
                'nama_akun' => 'Pendapatan Denda Keterlambatan',
                'tipe_akun' => 'pendapatan',
            ],
            [
                'kode_akun' => '4-2000',
                'nama_akun' => 'Pendapatan Usaha Lainnya',
                'tipe_akun' => 'pendapatan',
            ],
            [
                'kode_akun' => '4-3000',
                'nama_akun' => 'Pendapatan Lain-lain',
                'tipe_akun' => 'pendapatan',
            ],

            // ============================================
            // 5. BEBAN/BIAYA (Expenses)
            // ============================================
            [
                'kode_akun' => '5-1000',
                'nama_akun' => 'Beban Gaji dan Upah',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-1100',
                'nama_akun' => 'Beban Tunjangan',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-2000',
                'nama_akun' => 'Beban Listrik',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-2100',
                'nama_akun' => 'Beban Air',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-2200',
                'nama_akun' => 'Beban Telepon dan Internet',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-3000',
                'nama_akun' => 'Beban ATK (Alat Tulis Kantor)',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-3100',
                'nama_akun' => 'Beban Perlengkapan',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-4000',
                'nama_akun' => 'Beban Bunga Bank',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-4100',
                'nama_akun' => 'Beban Administrasi Bank',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-5000',
                'nama_akun' => 'Beban Penyusutan Peralatan',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-5100',
                'nama_akun' => 'Beban Penyusutan Gedung',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-5200',
                'nama_akun' => 'Beban Penyisihan Kerugian Piutang',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-6000',
                'nama_akun' => 'Beban Pemeliharaan dan Perbaikan',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-7000',
                'nama_akun' => 'Beban Transportasi',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-7100',
                'nama_akun' => 'Beban Perjalanan Dinas',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-8000',
                'nama_akun' => 'Beban Rapat dan Pertemuan',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-8100',
                'nama_akun' => 'Beban Konsumsi',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-9000',
                'nama_akun' => 'Beban Pajak',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-9100',
                'nama_akun' => 'Beban Kerugian Piutang',
                'tipe_akun' => 'beban',
            ],
            [
                'kode_akun' => '5-9900',
                'nama_akun' => 'Beban Lain-lain',
                'tipe_akun' => 'beban',
            ],
        ];

        foreach ($chartOfAccounts as $akun) {
            Akun::firstOrCreate(
                ['kode_akun' => $akun['kode_akun']],
                [
                    'nama_akun' => $akun['nama_akun'],
                    'tipe_akun' => $akun['tipe_akun'],
                    'status' => 'aktif',
                ]
            );
        }

        $this->command->info('✓ Chart of Accounts (COA) standar BUM Desa berhasil dibuat (global).');
    }
}
