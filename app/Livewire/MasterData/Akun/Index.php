<?php

namespace App\Livewire\MasterData\Akun;

use App\Models\Akun;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Master Akun'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?string $tipeFilter = null;

    public string $statusFilter = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'tipeFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        // Admin Desa dan Admin Kecamatan bisa melihat akun
        // Admin Kecamatan hanya bisa melihat (read-only), tidak bisa create/edit/delete
        Gate::authorize('view_desa_data');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTipeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function delete(int $akunId): void
    {
        Gate::authorize('manage_akun');

        $akun = Akun::findOrFail($akunId);

        // Catatan: Di fase selanjutnya, akan ada relasi ke modul Jurnal
        // Jika akun sudah digunakan dalam jurnal, tidak boleh dihapus
        // Untuk sekarang, akun bisa dihapus karena belum ada relasi ke modul lain

        $akun->delete();
        $this->dispatch('success', message: 'Akun berhasil dihapus.');
    }

    public function render()
    {
        $query = Akun::when($this->search, function ($query) {
            $query->where(function ($q) {
                $q->where('kode_akun', 'like', '%'.$this->search.'%')
                    ->orWhere('nama_akun', 'like', '%'.$this->search.'%');
            });
        })
            ->when($this->tipeFilter, function ($query) {
                $query->where('tipe_akun', $this->tipeFilter);
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('kode_akun');

        return view('livewire.master-data.akun.index', [
            'akun' => $query->paginate(10),
        ]);
    }
}
