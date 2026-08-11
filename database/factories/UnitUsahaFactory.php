<?php

namespace Database\Factories;

use App\Models\Desa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UnitUsaha>
 */
class UnitUsahaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'desa_id' => Desa::factory(),
            'kode_unit' => fake()->unique()->lexify('U???'),
            'nama_unit' => 'Unit '.ucwords(fake()->words(2, true)),
            'deskripsi' => fake()->sentence(),
            'status' => 'aktif',
        ];
    }
}
