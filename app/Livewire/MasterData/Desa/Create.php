<?php

namespace App\Livewire\MasterData\Desa;

use App\Models\Desa;
use App\Models\Kecamatan;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Tambah Desa'])]
class Create extends Component
{
    public string $nama_desa = '';

    public string $kode_desa = '';

    public ?int $kecamatan_id = null;

    public string $status = 'aktif';

    public function mount(): void
    {
        Gate::authorize('kelola_kabupaten');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'nama_desa' => ['required', 'string', 'max:255'],
            'kode_desa' => ['required', 'string', 'max:50', 'unique:desa,kode_desa'],
            'kecamatan_id' => ['required', 'exists:kecamatan,id'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ], [
            'nama_desa.required' => 'Nama desa wajib diisi.',
            'kode_desa.required' => 'Kode desa wajib diisi.',
            'kode_desa.unique' => 'Kode desa sudah digunakan.',
            'kecamatan_id.required' => 'Kecamatan wajib dipilih.',
            'kecamatan_id.exists' => 'Kecamatan yang dipilih tidak valid.',
            'status.required' => 'Status wajib dipilih.',
        ]);

        Desa::create($validated);

        $this->dispatch('success', message: 'Desa berhasil ditambahkan.');
        $this->redirect(route('desa.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.master-data.desa.create', [
            'kecamatan' => Kecamatan::aktif()->orderBy('nama_kecamatan')->get(),
        ]);
    }
}
