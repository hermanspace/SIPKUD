<?php

use App\Http\Controllers\LivewireFileUploadController;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

/*
| Custom Livewire upload endpoint - replaces default signed URL endpoint.
| Stabil di belakang Cloudflare/reverse proxy.
*/
Route::post('livewire/upload-file', LivewireFileUploadController::class)
    ->middleware(['web', 'auth'])
    ->name('livewire.upload-file');

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('user-manual', \App\Livewire\UserManual\Index::class)
    ->middleware(['auth'])
    ->name('user-manual.index');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    // Super Admin Routes - Master Data
    Route::middleware(['can:super_admin'])->group(function () {
        // Kecamatan CRUD
        Route::get('kecamatan', \App\Livewire\MasterData\Kecamatan\Index::class)->name('kecamatan.index');
        Route::get('kecamatan/create', \App\Livewire\MasterData\Kecamatan\Create::class)->name('kecamatan.create');
        Route::get('kecamatan/{kecamatan}/edit', \App\Livewire\MasterData\Kecamatan\Edit::class)->name('kecamatan.edit');
        
        // Desa CRUD
        Route::get('desa', \App\Livewire\MasterData\Desa\Index::class)->name('desa.index');
        Route::get('desa/create', \App\Livewire\MasterData\Desa\Create::class)->name('desa.create');
        Route::get('desa/{desa}/edit', \App\Livewire\MasterData\Desa\Edit::class)->name('desa.edit');
        
        // Pengumuman CRUD
        Route::get('pengumuman', \App\Livewire\MasterData\Pengumuman\Index::class)->name('pengumuman.index');
        
        // Pengaturan Sistem
        Route::get('pengaturan', \App\Livewire\MasterData\Pengaturan\Edit::class)->name('pengaturan.index');
    });

    // Super Admin & Admin Kecamatan Routes - Pengguna CRUD
    Route::middleware(['can:admin_kecamatan', 'tenant'])->group(function () {
        Route::get('pengguna', \App\Livewire\MasterData\Pengguna\Index::class)->name('pengguna.index');
        Route::get('pengguna/create', \App\Livewire\MasterData\Pengguna\Create::class)->name('pengguna.create');
        Route::get('pengguna/{user}/edit', \App\Livewire\MasterData\Pengguna\Edit::class)->name('pengguna.edit');
    });

    // Admin Desa & Admin Kecamatan Routes - Master Data
    // Index routes - bisa diakses oleh admin desa dan admin kecamatan (read-only untuk admin kecamatan)
    Route::middleware(['can:view_desa_data', 'tenant'])->group(function () {
        Route::get('kelompok', \App\Livewire\MasterData\Kelompok\Index::class)->name('kelompok.index');
        Route::get('anggota', \App\Livewire\MasterData\Anggota\Index::class)->name('anggota.index');
        Route::get('akun', \App\Livewire\MasterData\Akun\Index::class)->name('akun.index');
        Route::get('unit-usaha', \App\Livewire\MasterData\UnitUsaha\Index::class)->name('unit-usaha.index');
        Route::get('sektor-usaha', \App\Livewire\MasterData\SektorUsaha\Index::class)->name('sektor-usaha.index');
        Route::get('pinjaman', \App\Livewire\Pinjaman\Index::class)->name('pinjaman.index');
        Route::get('angsuran', \App\Livewire\Angsuran\Index::class)->name('angsuran.index');
        
        // Kas Harian
        Route::get('kas', \App\Livewire\Kas\Index::class)->name('kas.index');
        
        // Buku Memorial
        Route::get('memorial', \App\Livewire\Memorial\Index::class)->name('memorial.index');
        
        // Laporan
        Route::get('laporan/lpp-ued', \App\Livewire\Laporan\LppUed::class)->name('laporan.lpp-ued');
        Route::get('laporan/buku-kas', \App\Livewire\Laporan\BukuKas::class)->name('laporan.buku-kas');
        Route::get('laporan/akhir-usp', \App\Livewire\Laporan\LaporanAkhirUsp::class)->name('laporan.akhir-usp');
        Route::get('laporan/neraca-saldo', \App\Livewire\Laporan\NeracaSaldo::class)->name('laporan.neraca-saldo');
        Route::get('laporan/laba-rugi', \App\Livewire\Laporan\LabaRugi::class)->name('laporan.laba-rugi');
        Route::get('laporan/neraca', \App\Livewire\Laporan\Neraca::class)->name('laporan.neraca');
        
        // Periode Akuntansi
        Route::get('periode', \App\Livewire\Periode\Index::class)->name('periode.index');
        Route::get('periode/{desa_id}/{periode}', \App\Livewire\Periode\Show::class)->name('periode.show');
    });

    // Master Akun (COA) - hanya Super Admin & Admin Kecamatan
    Route::middleware(['can:manage_akun', 'tenant'])->group(function () {
        Route::get('akun/create', \App\Livewire\MasterData\Akun\Create::class)->name('akun.create');
        Route::get('akun/{akun}/edit', \App\Livewire\MasterData\Akun\Edit::class)->name('akun.edit');
    });

    // Admin Desa Routes - Create & Edit (admin kecamatan tidak bisa)
    Route::middleware(['can:admin_desa', 'tenant'])->group(function () {
        // Kelompok CRUD
        Route::get('kelompok/create', \App\Livewire\MasterData\Kelompok\Create::class)->name('kelompok.create');
        Route::get('kelompok/{kelompok}/edit', \App\Livewire\MasterData\Kelompok\Edit::class)->name('kelompok.edit');
        
        // Anggota CRUD
        Route::get('anggota/create', \App\Livewire\MasterData\Anggota\Create::class)->name('anggota.create');
        Route::get('anggota/{anggota}/edit', \App\Livewire\MasterData\Anggota\Edit::class)->name('anggota.edit');
        
        // Unit Usaha CRUD
        Route::get('unit-usaha/create', \App\Livewire\MasterData\UnitUsaha\Create::class)->name('unit-usaha.create');
        Route::get('unit-usaha/{id}/edit', \App\Livewire\MasterData\UnitUsaha\Edit::class)->name('unit-usaha.edit');
        
        // Pinjaman CRUD
        Route::get('pinjaman/create', \App\Livewire\Pinjaman\Create::class)->name('pinjaman.create');
        Route::get('pinjaman/{pinjaman}/edit', \App\Livewire\Pinjaman\Edit::class)->name('pinjaman.edit');
        
        // Angsuran CRUD
        Route::get('angsuran/create', \App\Livewire\Angsuran\Create::class)->name('angsuran.create');
        
        // Kas Harian CRUD
        Route::get('kas/create', \App\Livewire\Kas\Create::class)->name('kas.create');
        Route::get('kas/{id}/edit', \App\Livewire\Kas\Edit::class)->name('kas.edit');
        Route::get('kas/saldo-awal', \App\Livewire\Kas\SaldoAwal::class)->name('kas.saldo-awal');
        
        // Buku Memorial CRUD
        Route::get('memorial/create', \App\Livewire\Memorial\Create::class)->name('memorial.create');
        Route::get('memorial/{id}/edit', \App\Livewire\Memorial\Edit::class)->name('memorial.edit');
    });
});
