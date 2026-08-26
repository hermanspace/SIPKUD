<?php

namespace App\Livewire\MasterData\Kelompok;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Kelompok;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Master Kelompok'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $kecamatanFilter = null;

    public ?int $desaFilter = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'kecamatanFilter' => ['except' => null],
        'desaFilter' => ['except' => null],
    ];

    public function mount(): void
    {
        // Admin Desa dan Admin Kecamatan bisa melihat kelompok
        // Admin Kecamatan hanya bisa melihat (read-only), tidak bisa create/edit/delete
        Gate::authorize('view_desa_data');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingKecamatanFilter(): void
    {
        $this->resetPage();
        $this->reset('desaFilter');
    }

    public function updatingDesaFilter(): void
    {
        $this->resetPage();
    }

    public function delete(int $kelompokId): void
    {
        // Hanya Admin Desa yang bisa menghapus kelompok
        $user = Auth::user();
        if (! $user || ! $user->isAdminDesa()) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus kelompok.');
        }

        $kelompok = Kelompok::findOrFail($kelompokId);

        // Check if kelompok has anggota
        $anggotaCount = $kelompok->anggota()->count();
        if ($anggotaCount > 0) {
            $this->dispatch('error', message: "Tidak dapat menghapus kelompok yang memiliki {$anggotaCount} anggota.");

            return;
        }

        $kelompok->delete();
        $this->dispatch('success', message: 'Kelompok berhasil dihapus.');
    }

    public function render()
    {
        $user = Auth::user();

        $query = Kelompok::withCount('anggota')
            ->with('desa.kecamatan')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereLike('nama_kelompok', '%'.$this->search.'%')
                        ->orWhereLike('keterangan', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->kecamatanFilter, function ($query) {
                $query->whereHas('desa', function ($q) {
                    $q->where('kecamatan_id', $this->kecamatanFilter);
                });
            })
            ->when($this->desaFilter, function ($query) {
                $query->where('desa_id', $this->desaFilter);
            })
            ->orderBy('nama_kelompok');

        // Get kecamatan and desa for filters
        $kecamatan = collect();
        $desa = collect();

        if ($user && $user->hasKabupatenScope()) {
            $kecamatan = Kecamatan::aktif()->orderBy('nama_kecamatan')->get();
            if ($this->kecamatanFilter) {
                $desa = Desa::where('kecamatan_id', $this->kecamatanFilter)
                    ->aktif()
                    ->orderBy('nama_desa')
                    ->get();
            }
        } elseif ($user && $user->isAdminKecamatan()) {
            // Admin kecamatan bisa filter berdasarkan desa di kecamatannya
            $desa = Desa::where('kecamatan_id', $user->kecamatan_id)
                ->aktif()
                ->orderBy('nama_desa')
                ->get();
        }

        return view('livewire.master-data.kelompok.index', [
            'kelompok' => $query->paginate(10),
            'kecamatan' => $kecamatan,
            'desa' => $desa,
        ]);
    }
}
