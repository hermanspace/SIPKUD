<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\SektorUsaha;
use Illuminate\Database\Seeder;

/**
 * Seeder data dummy Sektor Usaha
 *
 * Membuat sektor usaha default per desa (untuk dropdown pinjaman).
 * Desa Kelapapati mendapat daftar lengkap; desa lain bisa di-seed opsional.
 */
class SektorUsahaSeeder extends Seeder
{
    protected array $namaSektorDefault = [
        'Pertanian',
        'Perdagangan',
        'Peternakan',
        'Perikanan',
        'Kerajinan',
        'Jasa',
        'Industri Kecil',
    ];

    public function run(): void
    {
        // Desa Kelapapati (untuk data testing/faker)
        $kelapapati = Desa::where('nama_desa', 'Desa Kelapapati')
            ->orWhere('kode_desa', 'DES005')
            ->first();

        if ($kelapapati) {
            foreach ($this->namaSektorDefault as $nama) {
                SektorUsaha::firstOrCreate(
                    [
                        'desa_id' => $kelapapati->id,
                        'nama' => $nama,
                    ],
                    [
                        'keterangan' => 'Sektor '.$nama.' untuk pinjaman anggota',
                        'status' => 'aktif',
                    ]
                );
            }
            $this->command->info('✓ Sektor usaha Desa Kelapapati: '.count($this->namaSektorDefault).' sektor.');
        }

        // Opsional: seed sektor untuk beberapa desa pertama (untuk konsistensi)
        $desaLain = Desa::whereNotIn('id', $kelapapati ? [$kelapapati->id] : [])
            ->take(3)
            ->get();

        foreach ($desaLain as $desa) {
            foreach (['Pertanian', 'Perdagangan', 'Jasa'] as $nama) {
                SektorUsaha::firstOrCreate(
                    [
                        'desa_id' => $desa->id,
                        'nama' => $nama,
                    ],
                    [
                        'status' => 'aktif',
                    ]
                );
            }
        }

        if ($desaLain->isNotEmpty()) {
            $this->command->info('✓ Sektor usaha untuk '.$desaLain->count().' desa tambahan.');
        }
    }
}
