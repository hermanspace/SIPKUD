<?php

namespace Database\Seeders;

use App\Models\Akun;
use App\Models\Anggota;
use App\Models\Desa;
use App\Models\Jurnal;
use App\Models\Kelompok;
use App\Models\TransaksiKas;
use App\Models\UnitUsaha;
use App\Models\User;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder untuk data testing
 *
 * Membuat data lengkap untuk Desa Kelapapati:
 * - User untuk testing
 * - Kelompok
 * - Anggota
 * - Akun (COA)
 * - Unit Usaha
 * - Transaksi Kas (Desember 2025 & Januari 2026)
 * - Jurnal Memorial (Desember 2025 & Januari 2026)
 */
class TestingDataSeeder extends Seeder
{
    protected $desa;

    protected $user;

    protected $accountingService;

    protected $unitUsahaUSP;

    protected $unitUsahaUMUM;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->accountingService = app(AccountingService::class);

        // 1. Get atau create Desa Kelapapati
        $this->desa = Desa::where('nama_desa', 'Desa Kelapapati')
            ->orWhere('kode_desa', 'DES005')
            ->first();

        if (! $this->desa) {
            $this->command->error('Desa Kelapapati tidak ditemukan. Jalankan DesaSeeder terlebih dahulu.');

            return;
        }

        $this->command->info("✓ Menggunakan Desa: {$this->desa->nama_desa} (ID: {$this->desa->id})");

        DB::transaction(function () {
            // 2. Create User untuk testing
            $this->createUsers();

            // 3. Create Kelompok
            $this->createKelompok();

            // 4. Create Anggota
            $this->createAnggota();

            // 5. Create Akun (COA)
            $this->createAkun();

            // 6. Create Unit Usaha
            $this->createUnitUsaha();

            // 7. Create Transaksi Kas untuk Desember 2025
            $this->createTransaksiKasDesember2025();

            // 8. Create Transaksi Kas untuk Januari 2026
            $this->createTransaksiKasJanuari2026();

            // 9. Create Jurnal Memorial untuk Desember 2025
            $this->createJurnalMemorialDesember2025();

            // 10. Create Jurnal Memorial untuk Januari 2026
            $this->createJurnalMemorialJanuari2026();

            // 11. Post ke Neraca Saldo
            $this->postToNeracaSaldo();
        });

        $this->command->info('✓ Testing data berhasil dibuat untuk Desa Kelapapati!');
    }

    /**
     * Create User untuk testing
     */
    protected function createUsers(): void
    {
        // Admin Desa
        $this->user = User::firstOrCreate(
            ['email' => 'admin.kelapapati@test.com'],
            [
                'nama' => 'Admin Desa Kelapapati',
                'password' => Hash::make('password'),
                'role' => 'admin_desa',
                'desa_id' => $this->desa->id,
            ]
        );

        $this->command->info("✓ User Admin Desa dibuat: {$this->user->email} (password: password)");
    }

    /**
     * Create Kelompok
     */
    protected function createKelompok(): void
    {
        $kelompokData = [
            ['nama_kelompok' => 'Kelompok Tani Sejahtera', 'keterangan' => 'Kelompok tani produktif'],
            ['nama_kelompok' => 'Kelompok Nelayan Mandiri', 'keterangan' => 'Kelompok nelayan'],
            ['nama_kelompok' => 'Kelompok Ibu PKK', 'keterangan' => 'Kelompok PKK'],
            ['nama_kelompok' => 'Kelompok Pemuda', 'keterangan' => 'Kelompok pemuda desa'],
        ];

        foreach ($kelompokData as $data) {
            Kelompok::firstOrCreate(
                [
                    'desa_id' => $this->desa->id,
                    'nama_kelompok' => $data['nama_kelompok'],
                ],
                [
                    'keterangan' => $data['keterangan'],
                    'status' => 'aktif',
                    'created_by' => $this->user->id,
                ]
            );
        }

        $this->command->info('✓ Kelompok berhasil dibuat');
    }

    /**
     * Create Anggota
     */
    protected function createAnggota(): void
    {
        $kelompok = Kelompok::where('desa_id', $this->desa->id)->first();

        $anggotaData = [
            ['nama' => 'Ahmad Hidayat', 'nik' => '1401010101010001', 'alamat' => 'Jl. Raya Kelapapati No. 1', 'nomor_hp' => '081234567890', 'jenis_kelamin' => 'L'],
            ['nama' => 'Siti Nurhaliza', 'nik' => '1401010101010002', 'alamat' => 'Jl. Raya Kelapapati No. 2', 'nomor_hp' => '081234567891', 'jenis_kelamin' => 'P'],
            ['nama' => 'Budi Santoso', 'nik' => '1401010101010003', 'alamat' => 'Jl. Raya Kelapapati No. 3', 'nomor_hp' => '081234567892', 'jenis_kelamin' => 'L'],
            ['nama' => 'Rina Wati', 'nik' => '1401010101010004', 'alamat' => 'Jl. Raya Kelapapati No. 4', 'nomor_hp' => '081234567893', 'jenis_kelamin' => 'P'],
            ['nama' => 'Joko Widodo', 'nik' => '1401010101010005', 'alamat' => 'Jl. Raya Kelapapati No. 5', 'nomor_hp' => '081234567894', 'jenis_kelamin' => 'L'],
            ['nama' => 'Maya Sari', 'nik' => '1401010101010006', 'alamat' => 'Jl. Raya Kelapapati No. 6', 'nomor_hp' => '081234567895', 'jenis_kelamin' => 'P'],
            ['nama' => 'Dedi Kurniawan', 'nik' => '1401010101010007', 'alamat' => 'Jl. Raya Kelapapati No. 7', 'nomor_hp' => '081234567896', 'jenis_kelamin' => 'L'],
            ['nama' => 'Lina Marlina', 'nik' => '1401010101010008', 'alamat' => 'Jl. Raya Kelapapati No. 8', 'nomor_hp' => '081234567897', 'jenis_kelamin' => 'P'],
            ['nama' => 'Rudi Hartono', 'nik' => '1401010101010009', 'alamat' => 'Jl. Raya Kelapapati No. 9', 'nomor_hp' => '081234567898', 'jenis_kelamin' => 'L'],
            ['nama' => 'Sari Indah', 'nik' => '1401010101010010', 'alamat' => 'Jl. Raya Kelapapati No. 10', 'nomor_hp' => '081234567899', 'jenis_kelamin' => 'P'],
        ];

        foreach ($anggotaData as $data) {
            Anggota::firstOrCreate(
                [
                    'desa_id' => $this->desa->id,
                    'nik' => $data['nik'],
                ],
                [
                    'kelompok_id' => $kelompok?->id,
                    'nama' => $data['nama'],
                    'alamat' => $data['alamat'],
                    'nomor_hp' => $data['nomor_hp'],
                    'jenis_kelamin' => $data['jenis_kelamin'],
                    'tanggal_gabung' => Carbon::now()->subMonths(rand(6, 24)),
                    'status' => 'aktif',
                    'created_by' => $this->user->id,
                ]
            );
        }

        $this->command->info('✓ Anggota berhasil dibuat (10 anggota)');
    }

    /**
     * Create Akun (COA) - global, digunakan seluruh desa
     */
    protected function createAkun(): void
    {
        $this->call(GlobalCoaSeeder::class);
    }

    /**
     * Create Unit Usaha
     */
    protected function createUnitUsaha(): void
    {
        // Unit Simpan Pinjam
        $this->unitUsahaUSP = UnitUsaha::firstOrCreate(
            [
                'desa_id' => $this->desa->id,
                'kode_unit' => 'USP',
            ],
            [
                'nama_unit' => 'Unit Simpan Pinjam',
                'status' => 'aktif',
                'created_by' => $this->user->id,
            ]
        );

        // Unit Usaha Umum
        $this->unitUsahaUMUM = UnitUsaha::firstOrCreate(
            [
                'desa_id' => $this->desa->id,
                'kode_unit' => 'UMUM',
            ],
            [
                'nama_unit' => 'Unit Usaha Umum',
                'status' => 'aktif',
                'created_by' => $this->user->id,
            ]
        );

        $this->command->info('✓ Unit Usaha berhasil dibuat');
    }

    /**
     * Create Transaksi Kas untuk Desember 2025
     */
    protected function createTransaksiKasDesember2025(): void
    {
        $akunKas = Akun::aktif()->where('nama_akun', 'Kas')->first();
        $akunPendapatan = Akun::aktif()->where('tipe_akun', 'pendapatan')->first();
        $akunBeban = Akun::aktif()->where('tipe_akun', 'beban')->first();

        if (! $akunKas || ! $akunPendapatan || ! $akunBeban) {
            $this->command->warn('⚠ Akun tidak lengkap, skip transaksi kas Desember 2025');

            return;
        }

        $transaksiData = [
            // Kas Masuk
            ['tanggal' => '2025-12-01', 'jenis' => 'masuk', 'jumlah' => 5000000, 'uraian' => 'Pendapatan simpanan anggota', 'akun_lawan' => $akunPendapatan->id],
            ['tanggal' => '2025-12-05', 'jenis' => 'masuk', 'jumlah' => 3000000, 'uraian' => 'Pendapatan jasa pinjaman', 'akun_lawan' => $akunPendapatan->id],
            ['tanggal' => '2025-12-10', 'jenis' => 'masuk', 'jumlah' => 2000000, 'uraian' => 'Pendapatan simpanan anggota', 'akun_lawan' => $akunPendapatan->id],
            ['tanggal' => '2025-12-15', 'jenis' => 'masuk', 'jumlah' => 4000000, 'uraian' => 'Pendapatan jasa pinjaman', 'akun_lawan' => $akunPendapatan->id],
            ['tanggal' => '2025-12-20', 'jenis' => 'masuk', 'jumlah' => 2500000, 'uraian' => 'Pendapatan simpanan anggota', 'akun_lawan' => $akunPendapatan->id],

            // Kas Keluar
            ['tanggal' => '2025-12-03', 'jenis' => 'keluar', 'jumlah' => 1500000, 'uraian' => 'Beban operasional', 'akun_lawan' => $akunBeban->id],
            ['tanggal' => '2025-12-08', 'jenis' => 'keluar', 'jumlah' => 2000000, 'uraian' => 'Beban gaji karyawan', 'akun_lawan' => $akunBeban->id],
            ['tanggal' => '2025-12-12', 'jenis' => 'keluar', 'jumlah' => 1000000, 'uraian' => 'Beban administrasi', 'akun_lawan' => $akunBeban->id],
            ['tanggal' => '2025-12-18', 'jenis' => 'keluar', 'jumlah' => 1800000, 'uraian' => 'Beban operasional', 'akun_lawan' => $akunBeban->id],
            ['tanggal' => '2025-12-25', 'jenis' => 'keluar', 'jumlah' => 1200000, 'uraian' => 'Beban administrasi', 'akun_lawan' => $akunBeban->id],
        ];

        foreach ($transaksiData as $data) {
            TransaksiKas::create([
                'desa_id' => $this->desa->id,
                'unit_usaha_id' => $this->unitUsahaUSP->id,
                'tanggal_transaksi' => $data['tanggal'],
                'jenis_transaksi' => $data['jenis'],
                'akun_kas_id' => $akunKas->id,
                'akun_lawan_id' => $data['akun_lawan'],
                'jumlah' => $data['jumlah'],
                'uraian' => $data['uraian'],
            ]);
        }

        $this->command->info('✓ Transaksi Kas Desember 2025 berhasil dibuat (10 transaksi)');
    }

    /**
     * Create Transaksi Kas untuk Januari 2026
     */
    protected function createTransaksiKasJanuari2026(): void
    {
        $akunKas = Akun::aktif()->where('nama_akun', 'Kas')->first();
        $akunPendapatan = Akun::aktif()->where('tipe_akun', 'pendapatan')->first();
        $akunBeban = Akun::aktif()->where('tipe_akun', 'beban')->first();

        if (! $akunKas || ! $akunPendapatan || ! $akunBeban) {
            $this->command->warn('⚠ Akun tidak lengkap, skip transaksi kas Januari 2026');

            return;
        }

        $transaksiData = [
            // Kas Masuk
            ['tanggal' => '2026-01-02', 'jenis' => 'masuk', 'jumlah' => 6000000, 'uraian' => 'Pendapatan simpanan anggota', 'akun_lawan' => $akunPendapatan->id],
            ['tanggal' => '2026-01-05', 'jenis' => 'masuk', 'jumlah' => 3500000, 'uraian' => 'Pendapatan jasa pinjaman', 'akun_lawan' => $akunPendapatan->id],
            ['tanggal' => '2026-01-10', 'jenis' => 'masuk', 'jumlah' => 2800000, 'uraian' => 'Pendapatan simpanan anggota', 'akun_lawan' => $akunPendapatan->id],
            ['tanggal' => '2026-01-15', 'jenis' => 'masuk', 'jumlah' => 4500000, 'uraian' => 'Pendapatan jasa pinjaman', 'akun_lawan' => $akunPendapatan->id],
            ['tanggal' => '2026-01-20', 'jenis' => 'masuk', 'jumlah' => 3200000, 'uraian' => 'Pendapatan simpanan anggota', 'akun_lawan' => $akunPendapatan->id],

            // Kas Keluar
            ['tanggal' => '2026-01-04', 'jenis' => 'keluar', 'jumlah' => 1800000, 'uraian' => 'Beban operasional', 'akun_lawan' => $akunBeban->id],
            ['tanggal' => '2026-01-08', 'jenis' => 'keluar', 'jumlah' => 2200000, 'uraian' => 'Beban gaji karyawan', 'akun_lawan' => $akunBeban->id],
            ['tanggal' => '2026-01-12', 'jenis' => 'keluar', 'jumlah' => 1200000, 'uraian' => 'Beban administrasi', 'akun_lawan' => $akunBeban->id],
            ['tanggal' => '2026-01-18', 'jenis' => 'keluar', 'jumlah' => 2000000, 'uraian' => 'Beban operasional', 'akun_lawan' => $akunBeban->id],
            ['tanggal' => '2026-01-25', 'jenis' => 'keluar', 'jumlah' => 1500000, 'uraian' => 'Beban administrasi', 'akun_lawan' => $akunBeban->id],
        ];

        foreach ($transaksiData as $data) {
            TransaksiKas::create([
                'desa_id' => $this->desa->id,
                'unit_usaha_id' => $this->unitUsahaUSP->id,
                'tanggal_transaksi' => $data['tanggal'],
                'jenis_transaksi' => $data['jenis'],
                'akun_kas_id' => $akunKas->id,
                'akun_lawan_id' => $data['akun_lawan'],
                'jumlah' => $data['jumlah'],
                'uraian' => $data['uraian'],
            ]);
        }

        $this->command->info('✓ Transaksi Kas Januari 2026 berhasil dibuat (10 transaksi)');
    }

    /**
     * Create Jurnal Memorial untuk Desember 2025
     */
    protected function createJurnalMemorialDesember2025(): void
    {
        $akunAset = Akun::aktif()
            ->where('tipe_akun', 'aset')
            ->where('nama_akun', 'like', '%Akumulasi%Penyusutan%')
            ->first();
        $akunBebanPenyusutan = Akun::aktif()
            ->where('tipe_akun', 'beban')
            ->where('nama_akun', 'like', '%Penyusutan%')
            ->first();

        if (! $akunAset || ! $akunBebanPenyusutan) {
            $this->command->warn('⚠ Akun tidak lengkap, skip jurnal memorial Desember 2025');

            return;
        }

        // Jurnal Penyusutan Aset
        $jurnal = $this->accountingService->createJurnal([
            'desa_id' => $this->desa->id,
            'unit_usaha_id' => $this->unitUsahaUSP->id,
            'tanggal_transaksi' => '2025-12-31',
            'jenis_jurnal' => 'memorial',
            'keterangan' => 'Penyusutan aset bulan Desember 2025',
            'status' => 'posted',
            'details' => [
                [
                    'akun_id' => $akunBebanPenyusutan->id,
                    'posisi' => 'debit',
                    'jumlah' => 500000,
                    'keterangan' => 'Beban penyusutan aset',
                ],
                [
                    'akun_id' => $akunAset->id,
                    'posisi' => 'kredit',
                    'jumlah' => 500000,
                    'keterangan' => 'Akumulasi penyusutan aset',
                ],
            ],
        ]);

        $this->command->info('✓ Jurnal Memorial Desember 2025 berhasil dibuat (1 jurnal)');
    }

    /**
     * Create Jurnal Memorial untuk Januari 2026
     */
    protected function createJurnalMemorialJanuari2026(): void
    {
        $akunAset = Akun::aktif()
            ->where('tipe_akun', 'aset')
            ->where('nama_akun', 'like', '%Akumulasi%Penyusutan%')
            ->first();
        $akunBebanPenyusutan = Akun::aktif()
            ->where('tipe_akun', 'beban')
            ->where('nama_akun', 'like', '%Penyusutan%')
            ->first();

        if (! $akunAset || ! $akunBebanPenyusutan) {
            $this->command->warn('⚠ Akun tidak lengkap, skip jurnal memorial Januari 2026');

            return;
        }

        // Jurnal Penyusutan Aset
        $jurnal = $this->accountingService->createJurnal([
            'desa_id' => $this->desa->id,
            'unit_usaha_id' => $this->unitUsahaUSP->id,
            'tanggal_transaksi' => '2026-01-31',
            'jenis_jurnal' => 'memorial',
            'keterangan' => 'Penyusutan aset bulan Januari 2026',
            'status' => 'posted',
            'details' => [
                [
                    'akun_id' => $akunBebanPenyusutan->id,
                    'posisi' => 'debit',
                    'jumlah' => 500000,
                    'keterangan' => 'Beban penyusutan aset',
                ],
                [
                    'akun_id' => $akunAset->id,
                    'posisi' => 'kredit',
                    'jumlah' => 500000,
                    'keterangan' => 'Akumulasi penyusutan aset',
                ],
            ],
        ]);

        $this->command->info('✓ Jurnal Memorial Januari 2026 berhasil dibuat (1 jurnal)');
    }

    /**
     * Post ke Neraca Saldo
     */
    protected function postToNeracaSaldo(): void
    {
        try {
            // Post Desember 2025
            $this->accountingService->recalculateBalance($this->desa->id, '2025-12');
            $this->command->info('✓ Neraca Saldo Desember 2025 berhasil diposting');

            // Post Januari 2026
            $this->accountingService->recalculateBalance($this->desa->id, '2026-01');
            $this->command->info('✓ Neraca Saldo Januari 2026 berhasil diposting');
        } catch (\Exception $e) {
            $this->command->warn("⚠ Error posting neraca saldo: {$e->getMessage()}");
        }
    }
}
