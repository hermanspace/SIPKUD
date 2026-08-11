<?php

namespace App\Livewire\MasterData\Kecamatan;

use App\Models\Kecamatan;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Master Kecamatan'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        Gate::authorize('kelola_kabupaten');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function delete(Kecamatan $kecamatan): void
    {
        // Check if kecamatan has desa
        $desaCount = $kecamatan->desa()->count();
        if ($desaCount > 0) {
            $this->dispatch('error', message: "Tidak dapat menghapus kecamatan yang memiliki {$desaCount} desa.");

            return;
        }

        $kecamatan->delete();
        $this->dispatch('success', message: 'Kecamatan berhasil dihapus.');
    }

    public function render()
    {
        $query = Kecamatan::withCount('desa')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nama_kecamatan', 'like', '%'.$this->search.'%')
                        ->orWhere('kode_kecamatan', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('nama_kecamatan');

        return view('livewire.master-data.kecamatan.index', [
            'kecamatan' => $query->paginate(10),
        ]);
    }
}
