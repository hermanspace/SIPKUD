<?php

namespace Database\Factories;

use App\Models\Desa;
use App\Models\Kecamatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Desa>
 */
class DesaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kecamatan_id' => Kecamatan::factory(),
            'nama_desa' => 'Desa '.fake()->unique()->streetName(),
            'kode_desa' => fake()->unique()->numerify('DES-####'),
            'status' => 'aktif',
        ];
    }
}
