<?php

use App\Models\Desa;
use App\Models\User;

function buatAdminKabupaten(): User
{
    return User::factory()->adminKabupaten()->create();
}

// ---------------------------------------------------------------- akses BOLEH

it('admin kabupaten dapat mengakses kelola wilayah dan pengumuman', function () {
    $this->actingAs(buatAdminKabupaten());

    $this->get(route('kecamatan.index'))->assertOk();
    $this->get(route('desa.index'))->assertOk();
    $this->get(route('pengumuman.index'))->assertOk();
});

it('admin kabupaten dapat mengelola pengguna dan master akun', function () {
    $this->actingAs(buatAdminKabupaten());

    $this->get(route('pengguna.index'))->assertOk();
    $this->get(route('pengguna.create'))->assertOk();
    $this->get(route('akun.index'))->assertOk();
    $this->get(route('akun.create'))->assertOk();
});

it('admin kabupaten dapat melihat data desa dan seluruh laporan', function () {
    $this->actingAs(buatAdminKabupaten());

    $this->get(route('anggota.index'))->assertOk();
    $this->get(route('pinjaman.index'))->assertOk();
    $this->get(route('kas.index'))->assertOk();
    $this->get(route('laporan.neraca-saldo'))->assertOk();
    $this->get(route('laporan.kolektibilitas'))->assertOk();
    $this->get(route('laporan.tahunan'))->assertOk();
    $this->get(route('periode.index'))->assertOk();
});

it('dashboard admin kabupaten menampilkan KPI kabupaten', function () {
    $this->actingAs(buatAdminKabupaten())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Admin Kabupaten (Dinas PMD)')
        ->assertSee('Ringkasan Keuangan');
});

it('admin kabupaten melihat semua desa (kabupaten scope)', function () {
    Desa::factory()->count(3)->create();

    expect(buatAdminKabupaten()->getAccessibleDesas())->toHaveCount(3)
        ->and(buatAdminKabupaten()->hasKabupatenScope())->toBeTrue();
});

// ------------------------------------------------------------- akses DILARANG

it('admin kabupaten TIDAK dapat mengakses pengaturan sistem dan backup', function () {
    $this->actingAs(buatAdminKabupaten());

    $this->get(route('pengaturan.index'))->assertForbidden();
    $this->get(route('backup.index'))->assertForbidden();
});

it('admin kabupaten TIDAK dapat membuka halaman input transaksi desa', function () {
    $this->actingAs(buatAdminKabupaten());

    $this->get(route('kas.create'))->assertForbidden();
    $this->get(route('pinjaman.create'))->assertForbidden();
    $this->get(route('angsuran.create'))->assertForbidden();
    $this->get(route('memorial.create'))->assertForbidden();
    $this->get(route('periode.tutup-tahun'))->assertForbidden();
});

it('admin kabupaten TIDAK dapat mengedit akun super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs(buatAdminKabupaten())
        ->get(route('pengguna.edit', $superAdmin))
        ->assertForbidden();
});

it('daftar pengguna admin kabupaten menyembunyikan akun super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create(['nama' => 'Operator Rahasia']);
    $adminDesa = User::factory()->adminDesa(Desa::factory()->create()->id)->create(['nama' => 'Admin Desa Biasa']);

    $this->actingAs(buatAdminKabupaten())
        ->get(route('pengguna.index'))
        ->assertOk()
        ->assertDontSee('Operator Rahasia')
        ->assertSee('Admin Desa Biasa');
});

it('kewenangan kelola akun terpusat di manageableRoles dan canManageUser', function () {
    $kabupaten = buatAdminKabupaten();
    $superAdmin = User::factory()->superAdmin()->create();
    $desa = Desa::factory()->create();
    $adminDesa = User::factory()->adminDesa($desa->id, $desa->kecamatan_id)->create();
    $kecamatan = User::factory()->adminKecamatan($desa->kecamatan_id)->create();

    expect($kabupaten->manageableRoles())->not->toContain('super_admin')
        ->and($kabupaten->canManageUser($superAdmin))->toBeFalse()
        ->and($kabupaten->canManageUser($adminDesa))->toBeTrue()
        ->and($kabupaten->canManageUser($kecamatan))->toBeTrue()
        ->and($superAdmin->manageableRoles())->toContain('super_admin', 'admin_kabupaten')
        ->and($kecamatan->canManageUser($adminDesa))->toBeTrue()
        ->and($kecamatan->canManageUser($kabupaten))->toBeFalse();
});

// -------------------------------------------------------- regresi role lama

it('super admin tetap dapat mengakses pengaturan, backup, dan wilayah', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $this->get(route('pengaturan.index'))->assertOk();
    $this->get(route('backup.index'))->assertOk();
    $this->get(route('kecamatan.index'))->assertOk();
    $this->get(route('pengguna.index'))->assertOk();
});

it('admin kecamatan tetap TIDAK dapat mengakses wilayah, pengaturan, dan backup', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->adminKecamatan($desa->kecamatan_id)->create());

    $this->get(route('kecamatan.index'))->assertForbidden();
    $this->get(route('pengaturan.index'))->assertForbidden();
    $this->get(route('backup.index'))->assertForbidden();
    $this->get(route('pengguna.index'))->assertOk();
});

it('admin desa tetap TIDAK dapat mengakses pengguna, wilayah, dan backup', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->adminDesa($desa->id, $desa->kecamatan_id)->create());

    $this->get(route('pengguna.index'))->assertForbidden();
    $this->get(route('kecamatan.index'))->assertForbidden();
    $this->get(route('backup.index'))->assertForbidden();
    $this->get(route('kas.create'))->assertOk();
});
