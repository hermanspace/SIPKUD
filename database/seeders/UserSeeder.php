<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        User::firstOrCreate(
            ['email' => 'superadmin@sipkud.local'],
            [
                'nama' => 'Super Admin PMD',
                'email' => 'superadmin@sipkud.local',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'kecamatan_id' => null,
                'desa_id' => null,
            ]
        );

        // Create Admin Kecamatan for kecamatan 1
        $firstKecamatan = Kecamatan::find(1);
        if ($firstKecamatan) {
            User::firstOrCreate(
                ['email' => 'adminkecamatan@sipkud.local'],
                [
                    'nama' => 'Admin Kecamatan '.$firstKecamatan->nama_kecamatan,
                    'email' => 'adminkecamatan@sipkud.local',
                    'password' => Hash::make('password'),
                    'role' => 'admin_kecamatan',
                    'kecamatan_id' => $firstKecamatan->id,
                    'desa_id' => null,
                ]
            );
        }

        // Create Admin Desa for several desa (ambil dari beberapa kecamatan berbeda)
        $desa = Desa::whereIn('kecamatan_id', [1, 2, 3, 4, 5])->take(5)->get();

        if ($desa->count() > 0) {
            foreach ($desa as $index => $d) {
                $email = 'admin'.($index + 1).'@sipkud.local';
                User::firstOrCreate(
                    ['email' => $email],
                    [
                        'nama' => 'Admin Desa '.$d->nama_desa,
                        'email' => $email,
                        'password' => Hash::make('password'),
                        'role' => 'admin_desa',
                        'kecamatan_id' => $d->kecamatan_id,
                        'desa_id' => $d->id,
                    ]
                );
            }
        }

        // Create one Executive View user
        $firstDesa = Desa::where('kecamatan_id', 1)->first();
        if ($firstDesa) {
            User::firstOrCreate(
                ['email' => 'executive@sipkud.local'],
                [
                    'nama' => 'Executive View',
                    'email' => 'executive@sipkud.local',
                    'password' => Hash::make('password'),
                    'role' => 'executive_view',
                    'kecamatan_id' => $firstDesa->kecamatan_id,
                    'desa_id' => $firstDesa->id,
                ]
            );
        }
    }
}
