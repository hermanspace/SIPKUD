<?php

namespace App\Livewire\MasterData\Desa;

use App\Models\Desa;
use App\Models\Kecamatan;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Master Desa'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $kecamatanFilter = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'kecamatanFilter' => ['except' => null],
    ];

    public function mount(): void
    {
        Gate::authorize('super_admin');
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
    }

    public function delete(Desa $desa): void
    {
        // Check if desa has related data
        $hasKelompok = $desa->kelompok()->count() > 0;
        $hasAnggota = $desa->anggota()->count() > 0;
        $hasAkun = $desa->akun()->count() > 0;
        $hasUsers = $desa->users()->count() > 0;

        if ($hasKelompok || $hasAnggota || $hasAkun || $hasUsers) {
            $messages = [];
            if ($hasKelompok) {
                $messages[] = 'kelompok';
            }
            if ($hasAnggota) {
                $messages[] = 'anggota';
            }
            if ($hasAkun) {
                $messages[] = 'akun';
            }
            if ($hasUsers) {
                $messages[] = 'pengguna';
            }

            $message = 'Tidak dapat menghapus desa yang memiliki data terkait: '.implode(', ', $messages).'.';
            $this->dispatch('error', message: $message);

            return;
        }

        $desa->delete();
        $this->dispatch('success', message: 'Desa berhasil dihapus.');
    }

    public function render()
    {
        $query = Desa::with('kecamatan')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nama_desa', 'like', '%'.$this->search.'%')
                        ->orWhere('kode_desa', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->kecamatanFilter, function ($query) {
                $query->where('kecamatan_id', $this->kecamatanFilter);
            })
            ->orderBy('nama_desa');

        return view('livewire.master-data.desa.index', [
            'desa' => $query->paginate(10),
            'kecamatan' => Kecamatan::aktif()->orderBy('nama_kecamatan')->get(),
        ]);
    }
}
