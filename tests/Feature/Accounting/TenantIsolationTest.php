<?php

use App\Models\Akun;
use App\Models\Desa;
use App\Models\Jurnal;
use App\Models\Kecamatan;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->kecamatanA = Kecamatan::factory()->create();
    $this->kecamatanB = Kecamatan::factory()->create();

    $this->desaA = Desa::factory()->create(['kecamatan_id' => $this->kecamatanA->id]);
    $this->desaB = Desa::factory()->create(['kecamatan_id' => $this->kecamatanA->id]);
    $this->desaC = Desa::factory()->create(['kecamatan_id' => $this->kecamatanB->id]);

    $this->adminDesaA = User::factory()->adminDesa($this->desaA->id, $this->kecamatanA->id)->create();
    $this->adminDesaB = User::factory()->adminDesa($this->desaB->id, $this->kecamatanA->id)->create();
    $this->adminDesaC = User::factory()->adminDesa($this->desaC->id, $this->kecamatanB->id)->create();
    $this->adminKecamatanA = User::factory()->adminKecamatan($this->kecamatanA->id)->create();
    $this->superAdmin = User::factory()->superAdmin()->create();

    $akunKas = Akun::factory()->tipe('aset')->create();
    $akunPendapatan = Akun::factory()->tipe('pendapatan')->create();

    $service = app(AccountingService::class);

    foreach ([$this->adminDesaA, $this->adminDesaB, $this->adminDesaC] as $admin) {
        Auth::login($admin);
        $service->createJurnal([
            'desa_id' => $admin->desa_id,
            'tanggal_transaksi' => '2026-03-10',
            'jenis_jurnal' => 'memorial',
            'keterangan' => 'Jurnal desa '.$admin->desa_id,
            'status' => 'posted',
            'details' => [
                ['akun_id' => $akunKas->id, 'posisi' => 'debit', 'jumlah' => 100000],
                ['akun_id' => $akunPendapatan->id, 'posisi' => 'kredit', 'jumlah' => 100000],
            ],
        ]);
        Auth::logout();
    }
});

it('membatasi admin desa hanya melihat jurnal desanya sendiri', function () {
    $this->actingAs($this->adminDesaA);

    $jurnals = Jurnal::all();

    expect($jurnals)->toHaveCount(1)
        ->and($jurnals->first()->desa_id)->toBe($this->desaA->id);
});

it('membatasi admin kecamatan hanya melihat jurnal desa di kecamatannya', function () {
    $this->actingAs($this->adminKecamatanA);

    $desaIds = Jurnal::all()->pluck('desa_id');

    expect($desaIds)->toHaveCount(2)
        ->and($desaIds)->toContain($this->desaA->id)
        ->and($desaIds)->toContain($this->desaB->id)
        ->and($desaIds)->not->toContain($this->desaC->id);
});

it('mengizinkan super admin melihat seluruh jurnal', function () {
    $this->actingAs($this->superAdmin);

    expect(Jurnal::count())->toBe(3);
});

it('memvalidasi akses desa melalui canAccessDesa sesuai hierarki role', function () {
    expect($this->adminDesaA->canAccessDesa($this->desaA->id))->toBeTrue()
        ->and($this->adminDesaA->canAccessDesa($this->desaB->id))->toBeFalse();

    expect($this->adminKecamatanA->canAccessDesa($this->desaA->id))->toBeTrue()
        ->and($this->adminKecamatanA->canAccessDesa($this->desaB->id))->toBeTrue()
        ->and($this->adminKecamatanA->canAccessDesa($this->desaC->id))->toBeFalse();

    expect($this->superAdmin->canAccessDesa($this->desaC->id))->toBeTrue();
});

it('membatasi daftar desa yang dapat diakses melalui getAccessibleDesas', function () {
    expect($this->adminDesaA->getAccessibleDesas()->pluck('id')->all())->toBe([$this->desaA->id]);

    $kecamatanDesas = $this->adminKecamatanA->getAccessibleDesas()->pluck('id');
    expect($kecamatanDesas)->toHaveCount(2)->and($kecamatanDesas)->not->toContain($this->desaC->id);

    expect($this->superAdmin->getAccessibleDesas())->toHaveCount(3);
});
