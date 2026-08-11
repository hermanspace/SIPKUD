<?php

namespace Database\Factories;

use App\Models\Akun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Akun>
 */
class AkunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_akun' => fake()->unique()->numerify('#-##-###'),
            'nama_akun' => ucwords(fake()->unique()->words(3, true)),
            'tipe_akun' => 'aset',
            'status' => 'aktif',
        ];
    }

    public function tipe(string $tipe): static
    {
        return $this->state(fn (array $attributes) => [
            'tipe_akun' => $tipe,
        ]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'nonaktif',
        ]);
    }
}
