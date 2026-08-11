<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Kelompok;
use Illuminate\Database\Seeder;

class AnggotaDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Generate 20 dummy anggota di beberapa desa di Kecamatan Bengkalis
     */
    public function run(): void
    {
        // Ambil Kecamatan Bengkalis
        $kecamatanBengkalis = Kecamatan::where('nama_kecamatan', 'Kecamatan Bengkalis')->first();

        if (! $kecamatanBengkalis) {
            $this->command->error('Kecamatan Bengkalis tidak ditemukan. Jalankan KecamatanSeeder terlebih dahulu.');

            return;
        }

        // Ambil beberapa desa di Kecamatan Bengkalis
        $desaList = Desa::where('kecamatan_id', $kecamatanBengkalis->id)
            ->whereIn('kode_desa', ['DES001', 'DES004', 'DES006', 'DES008', 'DES011', 'DES013'])
            ->get();

        if ($desaList->isEmpty()) {
            $this->command->error('Desa di Kecamatan Bengkalis tidak ditemukan. Jalankan DesaSeeder terlebih dahulu.');

            return;
        }

        // Buat kelompok untuk masing-masing desa jika belum ada
        $kelompokMap = [];
        foreach ($desaList as $desa) {
            $kelompok = Kelompok::firstOrCreate(
                [
                    'desa_id' => $desa->id,
                    'nama_kelompok' => 'Kelompok Mawar',
                ],
                [
                    'keterangan' => 'Kelompok binaan desa '.$desa->nama_desa,
                    'status' => 'aktif',
                ]
            );
            $kelompokMap[$desa->id] = $kelompok->id;
        }

        // Data anggota dummy
        $anggotaData = [
            // Kelurahan Bengkalis Kota (DES001)
            [
                'desa_kode' => 'DES001',
                'nama' => 'Ahmad Fauzi',
                'nik' => '1404011501850001',
                'alamat' => 'Jl. Antang Kalang No. 12',
                'nomor_hp' => '082174123456',
                'jenis_kelamin' => 'L',
                'tanggal_gabung' => '2023-01-15',
            ],
            [
                'desa_kode' => 'DES001',
                'nama' => 'Siti Nurhaliza',
                'nik' => '1404015203920002',
                'alamat' => 'Jl. Nusa Indah No. 8',
                'nomor_hp' => '081275234567',
                'jenis_kelamin' => 'P',
                'tanggal_gabung' => '2023-02-20',
            ],
            [
                'desa_kode' => 'DES001',
                'nama' => 'Budi Santoso',
                'nik' => '1404011912880003',
                'alamat' => 'Jl. Pramuka No. 45',
                'nomor_hp' => '085276345678',
                'jenis_kelamin' => 'L',
                'tanggal_gabung' => '2023-03-10',
            ],

            // Desa Air Putih (DES004)
            [
                'desa_kode' => 'DES004',
                'nama' => 'Dewi Lestari',
                'nik' => '1404046708910004',
                'alamat' => 'Dusun I, RT 02/RW 01',
                'nomor_hp' => '081377456789',
                'jenis_kelamin' => 'P',
                'tanggal_gabung' => '2023-01-25',
            ],
            [
                'desa_kode' => 'DES004',
                'nama' => 'Rudi Hartono',
                'nik' => '1404042301870005',
                'alamat' => 'Dusun II, RT 03/RW 02',
                'nomor_hp' => '082178567890',
                'jenis_kelamin' => 'L',
                'tanggal_gabung' => '2023-02-15',
            ],
            [
                'desa_kode' => 'DES004',
                'nama' => 'Ani Suryani',
                'nik' => '1404045511940006',
                'alamat' => 'Dusun III, RT 01/RW 01',
                'nomor_hp' => '085279678901',
                'jenis_kelamin' => 'P',
                'tanggal_gabung' => '2023-03-20',
            ],
            [
                'desa_kode' => 'DES004',
                'nama' => 'Hendra Wijaya',
                'nik' => '1404041409890007',
                'alamat' => 'Dusun I, RT 04/RW 02',
                'nomor_hp' => '081280789012',
                'jenis_kelamin' => 'L',
                'tanggal_gabung' => '2023-04-05',
            ],

            // Desa Kelemantan (DES006)
            [
                'desa_kode' => 'DES006',
                'nama' => 'Rahmat Hidayat',
                'nik' => '1404062908860008',
                'alamat' => 'Jl. Raya Kelemantan No. 23',
                'nomor_hp' => '082181890123',
                'jenis_kelamin' => 'L',
                'tanggal_gabung' => '2023-02-10',
            ],
            [
                'desa_kode' => 'DES006',
                'nama' => 'Sari Rahayu',
                'nik' => '1404064203930009',
                'alamat' => 'Jl. Mawar No. 7',
                'nomor_hp' => '085282901234',
                'jenis_kelamin' => 'P',
                'tanggal_gabung' => '2023-03-15',
            ],
            [
                'desa_kode' => 'DES006',
                'nama' => 'Dedi Kurniawan',
                'nik' => '1404061607880010',
                'alamat' => 'Jl. Melati No. 15',
                'nomor_hp' => '081283012345',
                'jenis_kelamin' => 'L',
                'tanggal_gabung' => '2023-04-20',
            ],

            // Desa Meskom (DES008)
            [
                'desa_kode' => 'DES008',
                'nama' => 'Wati Susilawati',
                'nik' => '1404085112920011',
                'alamat' => 'Kampung Meskom, RT 01/RW 01',
                'nomor_hp' => '082184123456',
                'jenis_kelamin' => 'P',
                'tanggal_gabung' => '2023-01-30',
            ],
            [
                'desa_kode' => 'DES008',
                'nama' => 'Agus Salim',
                'nik' => '1404081108850012',
                'alamat' => 'Kampung Meskom, RT 02/RW 01',
                'nomor_hp' => '085285234567',
                'jenis_kelamin' => 'L',
                'tanggal_gabung' => '2023-02-25',
            ],
            [
                'desa_kode' => 'DES008',
                'nama' => 'Linda Susanti',
                'nik' => '1404086409900013',
                'alamat' => 'Kampung Meskom, RT 03/RW 02',
                'nomor_hp' => '081286345678',
                'jenis_kelamin' => 'P',
                'tanggal_gabung' => '2023-03-30',
            ],
            [
                'desa_kode' => 'DES008',
                'nama' => 'Irfan Maulana',
                'nik' => '1404082505870014',
                'alamat' => 'Kampung Meskom, RT 04/RW 02',
                'nomor_hp' => '082187456789',
                'jenis_kelamin' => 'L',
                'tanggal_gabung' => '2023-04-15',
            ],

            // Desa Pematang Duku (DES011)
            [
                'desa_kode' => 'DES011',
                'nama' => 'Sinta Dewi',
                'nik' => '1404113006910015',
                'alamat' => 'Dusun Pematang, RT 01/RW 01',
                'nomor_hp' => '085288567890',
                'jenis_kelamin' => 'P',
                'tanggal_gabung' => '2023-02-05',
            ],
            [
                'desa_kode' => 'DES011',
                'nama' => 'Bambang Prakoso',
                'nik' => '1404111802840016',
                'alamat' => 'Dusun Pematang, RT 02/RW 01',
                'nomor_hp' => '081289678901',
                'jenis_kelamin' => 'L',
                'tanggal_gabung' => '2023-03-05',
            ],
            [
                'desa_kode' => 'DES011',
                'nama' => 'Maya Sari',
                'nik' => '1404114507930017',
                'alamat' => 'Dusun Pematang, RT 03/RW 02',
                'nomor_hp' => '082190789012',
                'jenis_kelamin' => 'P',
                'tanggal_gabung' => '2023-04-10',
            ],

            // Desa Penebal (DES013)
            [
                'desa_kode' => 'DES013',
                'nama' => 'Eko Prasetyo',
                'nik' => '1404132209860018',
                'alamat' => 'Jl. Penebal Raya No. 18',
                'nomor_hp' => '085291890123',
                'jenis_kelamin' => 'L',
                'tanggal_gabung' => '2023-02-28',
            ],
            [
                'desa_kode' => 'DES013',
                'nama' => 'Rina Marlina',
                'nik' => '1404135801920019',
                'alamat' => 'Jl. Penebal Raya No. 25',
                'nomor_hp' => '081292901234',
                'jenis_kelamin' => 'P',
                'tanggal_gabung' => '2023-03-25',
            ],
            [
                'desa_kode' => 'DES013',
                'nama' => 'Yoga Pratama',
                'nik' => '1404131204950020',
                'alamat' => 'Jl. Sentosa No. 12',
                'nomor_hp' => '082193012345',
                'jenis_kelamin' => 'L',
                'tanggal_gabung' => '2023-04-25',
            ],
        ];

        // Create anggota
        $desaMap = $desaList->keyBy('kode_desa');
        $created = 0;

        foreach ($anggotaData as $data) {
            $desa = $desaMap[$data['desa_kode']] ?? null;

            if (! $desa) {
                continue;
            }

            // Check if anggota already exists
            $existing = Anggota::where('nik', $data['nik'])->first();
            if ($existing) {
                $this->command->info("Anggota {$data['nama']} (NIK: {$data['nik']}) sudah ada, skip.");

                continue;
            }

            Anggota::create([
                'desa_id' => $desa->id,
                'kelompok_id' => $kelompokMap[$desa->id],
                'nama' => $data['nama'],
                'nik' => $data['nik'],
                'alamat' => $data['alamat'],
                'nomor_hp' => $data['nomor_hp'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'tanggal_gabung' => $data['tanggal_gabung'],
                'status' => 'aktif',
            ]);

            $created++;
            $this->command->info("Created: {$data['nama']} ({$desa->nama_desa})");
        }

        $this->command->info("✅ Selesai! Total {$created} anggota dummy berhasil dibuat di Kecamatan Bengkalis.");
    }
}
