<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TenantMiddleware
 *
 * Middleware untuk memastikan user hanya dapat mengakses data dari desa mereka
 * Super Admin dapat mengakses semua data tanpa batasan
 * Admin Kecamatan dapat mengakses semua data di kecamatannya
 *
 * Catatan: Modul-modul berikut akan dikembangkan di fase selanjutnya:
 * - Pinjaman
 * - Kas
 * - Jurnal (Akuntansi)
 * - Aset
 * - Pelaporan
 */
class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // Super Admin & Admin Kabupaten dapat mengakses semua data
        if ($user->hasKabupatenScope()) {
            return $next($request);
        }

        // Admin Kecamatan dapat mengakses semua data di kecamatannya
        if ($user->isAdminKecamatan()) {
            // Admin Kecamatan harus memiliki kecamatan_id
            if (! $user->kecamatan_id) {
                abort(403, 'Anda tidak memiliki akses ke kecamatan tertentu.');
            }

            return $next($request);
        }

        // Admin Desa dan Executive View harus memiliki desa_id
        if (! $user->desa_id) {
            abort(403, 'Anda tidak memiliki akses ke desa tertentu.');
        }

        return $next($request);
    }
}
