<?php

namespace App\Livewire\MasterData\UnitUsaha;

use App\Models\UnitUsaha;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Master Unit Usaha'])]
class Index extends Component
{
    use WithPagination;

    public $search = '';

    public $status = '';

    public ?int $selectedDesaId = null;

    public function mount(): void
    {
        Gate::authorize('view_desa_data');

        $user = Auth::user();

        // Set default selectedDesaId untuk user yang punya desa_id
        if ($user->desa_id && ! $this->selectedDesaId) {
            $this->selectedDesaId = $user->desa_id;
        }

        // Untuk Super Admin dan Admin Kecamatan, set ke desa pertama yang dapat diakses
        if (! $this->selectedDesaId) {
            $accessibleDesas = $user->getAccessibleDesas();
            if ($accessibleDesas->isNotEmpty()) {
                $this->selectedDesaId = $accessibleDesas->first()->id;
            }
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();

        // Get list desa yang dapat diakses
        $accessibleDesas = $user->getAccessibleDesas();

        // Validasi selectedDesaId
        if (! $this->selectedDesaId || ! $user->canAccessDesa($this->selectedDesaId)) {
            return view('livewire.master-data.unit-usaha.index', [
                'unitUsaha' => collect([]),
                'desas' => $accessibleDesas,
                'error' => 'Silakan pilih desa untuk melihat unit usaha.',
            ]);
        }

        $query = UnitUsaha::query()
            ->where('desa_id', $this->selectedDesaId);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('kode_unit', 'like', '%'.$this->search.'%')
                    ->orWhere('nama_unit', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $unitUsaha = $query->orderBy('kode_unit')
            ->paginate(20);

        return view('livewire.master-data.unit-usaha.index', [
            'unitUsaha' => $unitUsaha,
            'desas' => $accessibleDesas,
        ]);
    }

    public function hapus($id): void
    {
        $user = Auth::user();

        // Hanya Super Admin dan Admin Desa yang boleh hapus
        if (! $user->isSuperAdmin() && ! $user->isAdminDesa()) {
            $this->dispatch('error', message: 'Anda tidak memiliki akses untuk menghapus unit usaha.');

            return;
        }

        $unitUsaha = UnitUsaha::findOrFail($id);

        // Validasi akses ke desa
        if (! $user->canAccessDesa($unitUsaha->desa_id)) {
            $this->dispatch('error', message: 'Anda tidak memiliki akses ke desa ini.');

            return;
        }

        $unitUsaha->delete();

        $this->dispatch('success', message: 'Unit usaha berhasil dihapus.');
    }
}
