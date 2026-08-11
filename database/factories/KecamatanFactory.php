<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kecamatan>
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
