<?php

use App\Livewire\MasterData\Backup\Index;
use App\Models\Desa;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    // Direktori backup terisolasi per test run
    $this->backupDir = sys_get_temp_dir().'/sipkud-backup-test-'.uniqid();
    config()->set('database_backup.directory', $this->backupDir);
});

afterEach(function () {
    if (is_dir($this->backupDir)) {
        array_map('unlink', glob($this->backupDir.'/*') ?: []);
        rmdir($this->backupDir);
    }
});

it('menolak tamu mengakses halaman backup', function () {
    $this->get(route('backup.index'))->assertRedirect(route('login'));
});

it('menolak admin desa mengakses halaman backup', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->adminDesa($desa->id, $desa->kecamatan_id)->create();

    $this->actingAs($admin)
        ->get(route('backup.index'))
        ->assertForbidden();
});

it('mengizinkan super admin membuka halaman backup', function () {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('backup.index'))
        ->assertOk();
});

it('menampilkan daftar backup terurut dari yang terbaru', function () {
    $service = app(DatabaseBackupService::class);

    touch($service->directory().'/sipkud-manual-20260101-000000.dump', strtotime('2026-01-01'));
    touch($service->directory().'/sipkud-manual-20260201-000000.dump', strtotime('2026-02-01'));

    $list = $service->list();

    expect($list)->toHaveCount(2)
        ->and($list[0]['filename'])->toBe('sipkud-manual-20260201-000000.dump');
});

it('menolak nama file backup yang tidak valid (path traversal)', function () {
    $service = app(DatabaseBackupService::class);

    expect(fn () => $service->path('../../.env'))->toThrow(RuntimeException::class);
    expect(fn () => $service->path('..%2fsecret.dump'))->toThrow(RuntimeException::class);
});

it('menghapus file backup dan mencatat audit log', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $service = app(DatabaseBackupService::class);
    touch($service->directory().'/sipkud-manual-20260101-000000.dump');

    $service->delete('sipkud-manual-20260101-000000.dump');

    expect($service->list())->toBeEmpty();
    $this->assertDatabaseHas('audit_logs', ['action' => 'backup_deleted']);
});

it('memangkas backup lama sesuai retensi', function () {
    $service = app(DatabaseBackupService::class);

    foreach (range(1, 5) as $i) {
        touch($service->directory()."/sipkud-scheduled-2026010{$i}-000000.dump", strtotime("2026-01-0{$i}"));
    }

    $deleted = $service->prune(keep: 2);

    expect($deleted)->toBe(3)
        ->and($service->list())->toHaveCount(2)
        ->and($service->list()[0]['filename'])->toBe('sipkud-scheduled-20260105-000000.dump');
});

it('menerima unggahan file backup .dump ke daftar backup', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->set('uploadFile', UploadedFile::fake()->create('produksi.dump', 100))
        ->call('upload')
        ->assertHasNoErrors();

    $list = app(DatabaseBackupService::class)->list();

    expect($list)->toHaveCount(1)
        ->and($list[0]['filename'])->toStartWith('upload-')
        ->and($list[0]['filename'])->toEndWith('.dump');
});

it('menolak unggahan file dengan format selain .dump / .sql.gz', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->set('uploadFile', UploadedFile::fake()->create('bukan-backup.txt', 10))
        ->call('upload');

    expect(app(DatabaseBackupService::class)->list())->toBeEmpty();
});

it('menolak restore tanpa konfirmasi teks RESTORE', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $service = app(DatabaseBackupService::class);
    touch($service->directory().'/sipkud-manual-20260101-000000.dump');

    Livewire::test(Index::class)
        ->call('confirmRestore', 'sipkud-manual-20260101-000000.dump')
        ->set('confirmText', 'salah')
        ->call('restore');

    // File masih ada dan tidak ada audit restore yang tercatat
    expect($service->list())->toHaveCount(1);
    $this->assertDatabaseMissing('audit_logs', ['action' => 'backup_restored']);
});

it('membuat backup nyata lewat command db:backup', function () {
    $this->artisan('db:backup')->assertExitCode(0);

    $list = app(DatabaseBackupService::class)->list();

    expect($list)->toHaveCount(1)
        ->and($list[0]['size'])->toBeGreaterThan(0)
        ->and($list[0]['filename'])->toContain('scheduled');
})->skip(
    fn () => DB::connection()->getDriverName() === 'sqlite',
    'Backup nyata butuh PostgreSQL/MySQL (dijalankan di CI)'
);

it('menolak restore ketika format file tidak cocok dengan driver aktif', function () {
    $service = app(DatabaseBackupService::class);

    // Database pgsql tidak boleh menerima file .sql.gz (format MySQL)
    touch($service->directory().'/sipkud-manual-20260101-000000.sql.gz');

    expect(fn () => $service->restore('sipkud-manual-20260101-000000.sql.gz'))
        ->toThrow(RuntimeException::class);
})->skip(
    fn () => DB::connection()->getDriverName() !== 'pgsql',
    'Validasi format spesifik pgsql (dijalankan di CI)'
);
