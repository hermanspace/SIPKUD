<?php

namespace App\Providers;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Memaksa skema URL ke HTTPS di production untuk mencegah mixed content
        // (di belakang proxy/SSL termination). Tidak dipaksakan di local/testing.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Define gates for role-based access
        Gate::define('super_admin', function ($user) {
            return $user->isSuperAdmin();
        });

        Gate::define('admin_kecamatan', function ($user) {
            return $user->isAdminKecamatan() || $user->isSuperAdmin();
        });

        Gate::define('admin_desa', function ($user) {
            return $user->isAdminDesa();
        });

        // Gate untuk manage master akun (COA) - hanya Admin & Super Admin
        Gate::define('manage_akun', function ($user) {
            return $user->isSuperAdmin() || $user->isAdminKecamatan();
        });

        // Gate untuk read-only access - admin kecamatan bisa melihat data di kecamatannya
        Gate::define('view_desa_data', function ($user) {
            return $user->isAdminDesa() || $user->isAdminKecamatan() || $user->isSuperAdmin();
        });

        // Share pengaturan globally to all views
        View::composer('*', function ($view) {
            try {
                $pengaturan = Pengaturan::getSettings();
                $view->with('pengaturan', $pengaturan);
            } catch (\Exception $e) {
                // If table doesn't exist yet, use defaults
                $view->with('pengaturan', (object) [
                    'nama_instansi' => 'SIPKUD',
                    'nama_daerah' => 'Kabupaten',
                    'base_title' => 'SIPKUD - Sistem Informasi Pelaporan Keuangan USP Desa',
                    'logo_instansi' => null,
                    'favicon' => null,
                ]);
            }
        });
    }
}
