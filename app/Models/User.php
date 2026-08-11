<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * Model User (Pengguna)
 *
 * Sistem autentikasi dan otorisasi untuk SIPKUD
 * Mendukung 4 role: Super Admin PMD (Kabupaten), Admin Kecamatan, Admin Desa, Executive View
 *
 * Hierarki:
 * - Super Admin: dapat membuat Admin Kecamatan dan Admin Desa
 * - Admin Kecamatan: dapat membuat Admin Desa di kecamatannya
 * - Admin Desa: level terendah, mengelola data desa
 *
 * Catatan: Modul-modul berikut akan dikembangkan di fase selanjutnya:
 * - Pinjaman
 * - Kas
 * - Jurnal (Akuntansi)
 * - Aset
 * - Pelaporan
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'email',
        'password',
        'role',
        'kecamatan_id',
        'desa_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
        ];
    }

    /**
     * Relasi ke kecamatan
     */
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    /**
     * Relasi ke desa
     */
    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->nama)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Check if user is Super Admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is Admin Kecamatan
     */
    public function isAdminKecamatan(): bool
    {
        return $this->role === 'admin_kecamatan';
    }

    /**
     * Check if user is Admin Desa
     */
    public function isAdminDesa(): bool
    {
        return $this->role === 'admin_desa';
    }

    /**
     * Check if user is Executive View
     */
    public function isExecutiveView(): bool
    {
        return $this->role === 'executive_view';
    }

    /**
     * Check if user has read-only access
     */
    public function isReadOnly(): bool
    {
        return $this->isExecutiveView();
    }

    /**
     * Get list of accessible desa for current user based on role
     */
    public function getAccessibleDesas()
    {
        if ($this->isSuperAdmin()) {
            // Super Admin dapat akses semua desa
            return Desa::orderBy('nama_desa')->get();
        }

        if ($this->isAdminKecamatan()) {
            // Admin Kecamatan hanya dapat akses desa di kecamatannya
            return Desa::where('kecamatan_id', $this->kecamatan_id)
                ->orderBy('nama_desa')
                ->get();
        }

        if ($this->desa_id) {
            // Admin Desa dan lainnya hanya dapat akses desanya sendiri
            return Desa::where('id', $this->desa_id)->get();
        }

        return collect([]);
    }

    /**
     * Check if user can access specific desa
     */
    public function canAccessDesa(int $desaId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->isAdminKecamatan()) {
            $desa = Desa::find($desaId);

            return $desa && $desa->kecamatan_id == $this->kecamatan_id;
        }

        return $this->desa_id == $desaId;
    }
}
