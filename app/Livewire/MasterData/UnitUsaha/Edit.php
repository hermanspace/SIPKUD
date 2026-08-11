<?php

namespace App\Livewire\MasterData\UnitUsaha;

use App\Models\UnitUsaha;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Edit Unit Usaha'])]
class Edit extends Component
{
    public UnitUsaha $unitUsaha;

    public $kode_unit;

    public $nama_unit;

    public $deskripsi;

    public $status;

    public function mount($id): void
    {
        $user = Auth::user();

        // Hanya Super Admin dan Admin Desa yang boleh edit
        if (! $user->isSuperAdmin() && ! $user->isAdminDesa()) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit unit usaha.');
        }

        $this->unitUsaha = UnitUsaha::findOrFail($id);

        // Validasi akses desa
        if (! $user->canAccessDesa($this->unitUsaha->desa_id)) {
            abort(403, 'Anda tidak memiliki akses ke desa ini.');
        }

        $this->kode_unit = $this->unitUsaha->kode_unit;
        $this->nama_unit = $this->unitUsaha->nama_unit;
        $this->deskripsi = $this->unitUsaha->deskripsi;
        $this->status = $this->unitUsaha->status;
    }

    public function update(): void
    {
        $user = Auth::user();

        // Hanya Super Admin dan Admin Desa yang boleh edit
        if (! $user->isSuperAdmin() && ! $user->isAdminDesa()) {
            $this->dispatch('error', message: 'Anda tidak memiliki akses untuk mengedit unit usaha.');

            return;
        }

        // Validasi akses desa
        if (! $user->canAccessDesa($this->unitUsaha->desa_id)) {
            $this->dispatch('error', message: 'Anda tidak memiliki akses ke desa ini.');

            return;
        }

        $this->validate([
            'kode_unit' => 'required|string|max:20',
            'nama_unit' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        // Cek duplikasi kode unit (kecuali diri sendiri)
        $exists = UnitUsaha::where('desa_id', $this->unitUsaha->desa_id)
            ->where('kode_unit', $this->kode_unit)
            ->where('id', '!=', $this->unitUsaha->id)
            ->exists();

        if ($exists) {
            $this->addError('kode_unit', 'Kode unit sudah digunakan.');

            return;
        }

        $this->unitUsaha->update([
            'kode_unit' => $this->kode_unit,
            'nama_unit' => $this->nama_unit,
            'deskripsi' => $this->deskripsi,
            'status' => $this->status,
            'updated_by' => $user->id,
        ]);

        $this->dispatch('success', message: 'Unit usaha berhasil diupdate.');
        $this->redirect('/unit-usaha', navigate: true);
    }

    public function render()
    {
        return view('livewire.master-data.unit-usaha.edit');
    }
}
