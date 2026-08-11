<?php

namespace App\Livewire\MasterData\UnitUsaha;

use App\Models\UnitUsaha;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Tambah Unit Usaha'])]
class Create extends Component
{
    public $kode_unit;

    public $nama_unit;

    public $deskripsi;

    public $status = 'aktif';

    public $desa_id;

    public function mount(): void
    {
        $user = Auth::user();

        // Hanya Super Admin dan Admin Desa yang boleh create
        if (! $user->isSuperAdmin() && ! $user->isAdminDesa()) {
            abort(403, 'Anda tidak memiliki akses untuk membuat unit usaha.');
        }

        // Set default desa_id untuk Admin Desa
        if ($user->desa_id) {
            $this->desa_id = $user->desa_id;
        }
    }

    public function save(): void
    {
        $user = Auth::user();

        // Hanya Super Admin dan Admin Desa yang boleh create
        if (! $user->isSuperAdmin() && ! $user->isAdminDesa()) {
            $this->dispatch('error', message: 'Anda tidak memiliki akses untuk membuat unit usaha.');

            return;
        }

        $validationRules = [
            'kode_unit' => 'required|string|max:20',
            'nama_unit' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:aktif,nonaktif',
        ];

        $validationMessages = [
            'kode_unit.required' => 'Kode unit harus diisi',
            'nama_unit.required' => 'Nama unit harus diisi',
            'status.required' => 'Status harus dipilih',
        ];

        // Validasi desa_id untuk Super Admin
        if (! $user->desa_id) {
            $validationRules['desa_id'] = 'required|exists:desas,id';
            $validationMessages['desa_id.required'] = 'Desa harus dipilih';
        }

        $this->validate($validationRules, $validationMessages);

        // Validasi akses desa
        if (! $user->canAccessDesa($this->desa_id)) {
            $this->dispatch('error', message: 'Anda tidak memiliki akses ke desa ini.');

            return;
        }

        // Cek duplikasi kode unit
        $exists = UnitUsaha::where('desa_id', $this->desa_id)
            ->where('kode_unit', $this->kode_unit)
            ->exists();

        if ($exists) {
            $this->addError('kode_unit', 'Kode unit sudah digunakan.');

            return;
        }

        UnitUsaha::create([
            'desa_id' => $this->desa_id,
            'kode_unit' => $this->kode_unit,
            'nama_unit' => $this->nama_unit,
            'deskripsi' => $this->deskripsi,
            'status' => $this->status,
            'created_by' => $user->id,
        ]);

        $this->dispatch('success', message: 'Unit usaha berhasil ditambahkan.');
        $this->redirect('/unit-usaha', navigate: true);
    }

    public function render()
    {
        $user = Auth::user();
        $desas = $user->getAccessibleDesas();

        return view('livewire.master-data.unit-usaha.create', [
            'desas' => $desas,
        ]);
    }
}
