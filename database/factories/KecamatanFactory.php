<?php

namespace Database\Factories;

use App\Models\Kecamatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kecamatan>
 */
class KecamatanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_kecamatan' => 'Kecamatan '.fake()->unique()->city(),
            'kode_kecamatan' => fake()->unique()->numerify('KEC-####'),
            'status' => 'aktif',
        ];
    }
}
