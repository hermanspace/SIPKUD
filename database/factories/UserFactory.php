<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => Str::random(10),
            'two_factor_recovery_codes' => Str::random(10),
            'two_factor_confirmed_at' => now(),
        ];
    }

    /**
     * Role: Super Admin (PMD Kabupaten) - akses seluruh data.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'super_admin',
            'kecamatan_id' => null,
            'desa_id' => null,
        ]);
    }

    /**
     * Role: Admin Kecamatan - akses data seluruh desa di kecamatannya.
     */
    public function adminKecamatan(int $kecamatanId): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin_kecamatan',
            'kecamatan_id' => $kecamatanId,
            'desa_id' => null,
        ]);
    }

    /**
     * Role: Admin Desa - hanya akses data desanya sendiri.
     */
    public function adminDesa(int $desaId, ?int $kecamatanId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin_desa',
            'kecamatan_id' => $kecamatanId,
            'desa_id' => $desaId,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model does not have two-factor authentication configured.
     */
    public function withoutTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }
}
