<?php

namespace App\Livewire\MasterData\ImportHistoris;

use App\Models\Akun;
use App\Models\UnitUsaha;
use App\Services\ImportHistorisService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Impor data historis pemanfaat + pinjaman dari Excel UEK-SP (sheet LPP-UEK).
 * Alur: unggah -> tinjau (ringkasan + kontrol + galat) -> tulis -> hasil.
 */
#[Layout('components.layouts.app', ['title' => 'Impor Data Historis'])]
class Index extends Component
{
    use WithFileUploads;

    public $uploadFile;

    public int $step = 1;

    public string $storedPath = '';

    public ?string $periode = null;

    public array $ringkasan = [];

    public array $kontrol = [];

    public array $galat = [];

    public ?int $unit_usaha_id = null;

    public ?int $akun_piutang_id = null;

    public ?int $akun_modal_id = null;

    public array $hasil = [];

    public string $pesanError = '';

    public function mount(): void
    {
        Gate::authorize('admin_desa');

        $desaId = Auth::user()->desa_id;

        $this->unit_usaha_id = UnitUsaha::where('desa_id', $desaId)->aktif()
            ->orderBy('id')->value('id');

        $this->akun_piutang_id = Akun::aktif()
            ->where('tipe_akun', 'aset')
            ->where('nama_akun', 'like', '%Piutang%')
            ->orderBy('kode_akun')->value('id');

        $this->akun_modal_id = Akun::aktif()
            ->where('tipe_akun', 'ekuitas')
            ->where('nama_akun', 'like', '%Modal%')
            ->orderBy('kode_akun')->value('id');
    }

    public function updatedUploadFile(ImportHistorisService $service): void
    {
        $this->pesanError = '';
        $this->validate(
            ['uploadFile' => ['required', 'file', 'max:20480']],
            ['uploadFile.max' => 'Ukuran file maksimal 20 MB.'],
        );

        $ext = strtolower($this->uploadFile->getClientOriginalExtension());
        if (! in_array($ext, ['xls', 'xlsx'])) {
            $this->pesanError = 'File harus berformat Excel (.xls atau .xlsx).';
            $this->reset('uploadFile');

            return;
        }

        // Disk 'local' eksplisit: unggahan Livewire di mode test memakai disk
        // fake internal - tanpa ini file tersimpan di tempat berbeda dari
        // yang dibaca Storage::path().
        $this->storedPath = $this->uploadFile->storeAs(
            'import-historis',
            sprintf('desa-%d-%s.%s', Auth::user()->desa_id, now()->format('YmdHis'), $ext),
            'local',
        );

        try {
            $parsed = $service->parse(Storage::path($this->storedPath));
        } catch (\Throwable $e) {
            Storage::delete($this->storedPath);
            $this->reset('uploadFile', 'storedPath');
            $this->pesanError = $e->getMessage();

            return;
        }

        $this->periode = $parsed['periode'];
        $this->ringkasan = $parsed['ringkasan'];
        $this->kontrol = $parsed['kontrol'];
        $this->galat = $parsed['errors'];
        $this->step = 2;
    }

    public function batal(): void
    {
        if ($this->storedPath !== '') {
            Storage::delete($this->storedPath);
        }
        $this->reset('uploadFile', 'storedPath', 'periode', 'ringkasan', 'kontrol', 'galat', 'hasil', 'pesanError');
        $this->step = 1;
    }

    public function jalankan(ImportHistorisService $service): void
    {
        Gate::authorize('admin_desa');

        $this->validate([
            'unit_usaha_id' => ['required', 'exists:unit_usaha,id'],
            'akun_piutang_id' => ['required', 'exists:akun,id', 'different:akun_modal_id'],
            'akun_modal_id' => ['required', 'exists:akun,id'],
        ], [
            'unit_usaha_id.required' => 'Unit usaha harus dipilih.',
            'akun_piutang_id.required' => 'Akun piutang harus dipilih.',
            'akun_piutang_id.different' => 'Akun piutang dan akun modal tidak boleh sama.',
            'akun_modal_id.required' => 'Akun modal harus dipilih.',
        ]);

        if ($this->storedPath === '' || ! Storage::exists($this->storedPath)) {
            $this->pesanError = 'File unggahan tidak ditemukan. Silakan unggah ulang.';
            $this->step = 1;

            return;
        }

        try {
            $parsed = $service->parse(Storage::path($this->storedPath));

            $this->hasil = $service->import(
                desaId: Auth::user()->desa_id,
                parsed: $parsed,
                unitUsahaId: $this->unit_usaha_id,
                akunPiutangId: $this->akun_piutang_id,
                akunModalId: $this->akun_modal_id,
                userId: Auth::id(),
            );
        } catch (\Throwable $e) {
            $this->pesanError = 'Impor gagal dan seluruh perubahan dibatalkan: '.$e->getMessage();

            return;
        } finally {
            Storage::delete($this->storedPath);
            $this->storedPath = '';
        }

        $this->step = 3;
    }

    public function render()
    {
        $desaId = Auth::user()->desa_id;

        return view('livewire.master-data.import-historis.index', [
            'units' => UnitUsaha::where('desa_id', $desaId)->aktif()->orderBy('nama_unit')->get(),
            'akunAset' => Akun::aktif()->where('tipe_akun', 'aset')->orderBy('kode_akun')->get(),
            'akunEkuitas' => Akun::aktif()->where('tipe_akun', 'ekuitas')->orderBy('kode_akun')->get(),
        ]);
    }
}
