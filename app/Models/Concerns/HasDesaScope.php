<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Trait untuk menambahkan scope desa_id pada model tenant
 *
 * Scope ini akan otomatis memfilter data berdasarkan desa_id user yang sedang login.
 * Super Admin dapat mengakses semua data tanpa filter.
 * Admin Kecamatan dapat mengakses semua data di kecamatannya.
 *
 * Catatan: Modul-modul berikut akan dikembangkan di fase selanjutnya:
 * - Pinjaman
 * - Kas
 * - Jurnal (Akuntansi)
 * - Aset
 * - Pelaporan
 */
trait HasDesaScope
{
    /**
     * Boot the scope
     */
    protected static function bootHasDesaScope(): void
    {
        static::addGlobalScope('desa', function (Builder $builder) {
            // Skip scope jika tidak ada user yang login (untuk seeder, artisan commands, dll)
            if (! Auth::check()) {
                return;
            }

            $user = Auth::user();

            // Super Admin dapat mengakses semua data
            if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return;
            }

            // Admin Kecamatan dapat mengakses semua data di kecamatannya
            if ($user && method_exists($user, 'isAdminKecamatan') && $user->isAdminKecamatan() && $user->kecamatan_id) {
                // Filter berdasarkan kecamatan_id - ambil semua desa di kecamatan tersebut
                // Menggunakan whereHas untuk memfilter desa yang memiliki kecamatan_id yang sama
                $builder->whereHas('desa', function ($query) use ($user) {
                    $query->where('kecamatan_id', $user->kecamatan_id);
                });

                return;
            }

            // Filter berdasarkan desa_id user yang sedang login (untuk Admin Desa)
            if ($user && $user->desa_id) {
                $builder->where('desa_id', $user->desa_id);
            }
        });
    }
}
