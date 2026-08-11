<?php

namespace App\Livewire\MasterData\Backup;

use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

#[Layout('components.layouts.app', ['title' => 'Backup & Restore Database'])]
class Index extends Component
{
    use WithFileUploads;

    /** File backup yang dipilih untuk restore (menunggu konfirmasi) */
    public ?string $restoreTarget = null;

    /** Teks konfirmasi yang wajib diketik sebelum restore */
    public string $confirmText = '';

    /** File backup yang diunggah dari komputer (untuk restore lintas server) */
    public $uploadFile = null;

    public function mount(): void
    {
        Gate::authorize('super_admin');
    }

    /**
     * Buat backup baru sekarang.
     */
    public function createBackup(DatabaseBackupService $backup): void
    {
        Gate::authorize('super_admin');

        set_time_limit(0);

        try {
            $result = $backup->create('manual');
            session()->flash('message', "Backup berhasil dibuat: {$result['filename']}");
        } catch (Throwable $e) {
            session()->flash('error', 'Backup gagal: '.$e->getMessage());
        }
    }

    /**
     * Unduh file backup ke komputer.
     */
    public function download(DatabaseBackupService $backup, string $filename): ?BinaryFileResponse
    {
        Gate::authorize('super_admin');

        try {
            return response()->download($backup->path($filename));
        } catch (Throwable $e) {
            session()->flash('error', $e->getMessage());

            return null;
        }
    }

    /**
     * Unggah file backup dari komputer (mis. hasil unduhan dari server lain).
     */
    public function upload(DatabaseBackupService $backup): void
    {
        Gate::authorize('super_admin');

        $this->validate(
            ['uploadFile' => 'required|file|max:512000'], // maks 500MB
            ['uploadFile.required' => 'Pilih file backup terlebih dahulu.']
        );

        $original = $this->uploadFile->getClientOriginalName();

        if (! preg_match('/\.(dump|sql\.gz)$/', $original)) {
            session()->flash('error', 'Format file harus .dump (PostgreSQL) atau .sql.gz (MySQL).');

            return;
        }

        // Nama file disanitasi + diberi prefix agar jelas asalnya dari unggahan
        $safeName = 'upload-'.now()->format('Ymd-His').'-'
            .preg_replace('/[^A-Za-z0-9._-]/', '_', $original);

        $this->uploadFile->storeAs('backups-tmp', $safeName, ['disk' => 'local']);
        rename(
            Storage::disk('local')->path('backups-tmp/'.$safeName),
            $backup->directory().'/'.$safeName
        );

        $this->uploadFile = null;
        session()->flash('message', "File backup berhasil diunggah: {$safeName}. Klik Restore untuk memulihkannya.");
    }

    /**
     * Tampilkan dialog konfirmasi restore.
     */
    public function confirmRestore(string $filename): void
    {
        Gate::authorize('super_admin');

        $this->restoreTarget = $filename;
        $this->confirmText = '';
    }

    public function cancelRestore(): void
    {
        $this->restoreTarget = null;
        $this->confirmText = '';
    }

    /**
     * Jalankan restore setelah konfirmasi eksplisit.
     */
    public function restore(DatabaseBackupService $backup): void
    {
        Gate::authorize('super_admin');

        if (! $this->restoreTarget) {
            return;
        }

        if ($this->confirmText !== 'RESTORE') {
            session()->flash('error', 'Ketik RESTORE (huruf besar) untuk mengonfirmasi.');

            return;
        }

        set_time_limit(0);

        try {
            $result = $backup->restore($this->restoreTarget);

            $pesan = "Database berhasil di-restore dari {$this->restoreTarget}. "
                ."Kondisi sebelumnya tersimpan sebagai {$result['safety_backup']}.";

            if (! $result['integrity_ok']) {
                $pesan .= ' PERHATIAN: verifikasi integritas akuntansi menemukan ketidaksesuaian - periksa log.';
                session()->flash('error', $pesan);
            } else {
                $pesan .= ' Verifikasi integritas akuntansi: OK.';
                session()->flash('message', $pesan);
            }
        } catch (Throwable $e) {
            session()->flash('error', 'Restore gagal: '.$e->getMessage());
        } finally {
            $this->restoreTarget = null;
            $this->confirmText = '';
        }
    }

    /**
     * Hapus file backup.
     */
    public function delete(DatabaseBackupService $backup, string $filename): void
    {
        Gate::authorize('super_admin');

        try {
            $backup->delete($filename);
            session()->flash('message', "File backup dihapus: {$filename}");
        } catch (Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render(DatabaseBackupService $backup)
    {
        return view('livewire.master-data.backup.index', [
            'backups' => $backup->list(),
        ]);
    }
}
