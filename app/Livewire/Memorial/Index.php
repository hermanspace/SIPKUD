<?php

namespace App\Livewire\Memorial;

use App\Models\Jurnal;
use App\Models\UnitUsaha;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Buku Memorial'])]
class Index extends Component
{
    use WithPagination;

    public $search = '';

    public $unitFilter = '';

    public $statusFilter = '';

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

    public function render()
    {
        $user = Auth::user();

        // Get list desa yang dapat diakses
        $accessibleDesas = $user->getAccessibleDesas();

        // Validasi selectedDesaId
        if (! $this->selectedDesaId || ! $user->canAccessDesa($this->selectedDesaId)) {
            return view('livewire.memorial.index', [
                'jurnal' => collect([]),
                'units' => collect([]),
                'desas' => $accessibleDesas,
                'error' => 'Silakan pilih desa untuk melihat buku memorial.',
            ]);
        }

        $query = Jurnal::query()
            ->with(['unitUsaha', 'details.akun', 'creator'])
            ->where('desa_id', $this->selectedDesaId)
            ->where('jenis_jurnal', 'umum');

        // Filter search
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereLike('nomor_jurnal', '%'.$this->search.'%')
                    ->orWhereLike('uraian', '%'.$this->search.'%');
            });
        }

        // Filter unit usaha
        if ($this->unitFilter) {
            $query->where('unit_usaha_id', $this->unitFilter);
        }

        // Filter status
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $jurnal = $query->orderBy('tanggal_jurnal', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        $units = UnitUsaha::where('desa_id', $this->selectedDesaId)
            ->aktif()
            ->orderBy('nama_unit')
            ->get();

        return view('livewire.memorial.index', [
            'jurnal' => $jurnal,
            'units' => $units,
            'desas' => $accessibleDesas,
        ]);
    }

    public function delete($id): void
    {
        $user = Auth::user();

        // Hanya Super Admin dan Admin Desa yang boleh hapus
        if (! $user->isSuperAdmin() && ! $user->isAdminDesa()) {
            $this->dispatch('error', message: 'Anda tidak memiliki akses untuk menghapus jurnal.');

            return;
        }

        $jurnal = Jurnal::findOrFail($id);

        // Validasi akses ke desa
        if (! $user->canAccessDesa($jurnal->desa_id)) {
            $this->dispatch('error', message: 'Anda tidak memiliki akses ke desa ini.');

            return;
        }

        // Validasi: hanya draft yang bisa dihapus
        if ($jurnal->status !== 'draft') {
            $this->dispatch('error', message: 'Hanya jurnal draft yang dapat dihapus.');

            return;
        }

        $jurnal->delete();
        $this->dispatch('success', message: 'Jurnal memorial berhasil dihapus.');
    }

    public function void($id): void
    {
        $user = Auth::user();

        // Hanya Super Admin dan Admin Desa yang boleh void
        if (! $user->isSuperAdmin() && ! $user->isAdminDesa()) {
            $this->dispatch('error', message: 'Anda tidak memiliki akses untuk void jurnal.');

            return;
        }

        $jurnal = Jurnal::findOrFail($id);

        // Validasi akses ke desa
        if (! $user->canAccessDesa($jurnal->desa_id)) {
            $this->dispatch('error', message: 'Anda tidak memiliki akses ke desa ini.');

            return;
        }

        // Validasi: hanya posted yang bisa di-void
        if ($jurnal->status !== 'posted') {
            $this->dispatch('error', message: 'Hanya jurnal posted yang dapat di-void.');

            return;
        }

        $jurnal->update(['status' => 'void']);
        $this->dispatch('success', message: 'Jurnal memorial berhasil di-void.');
    }
}
