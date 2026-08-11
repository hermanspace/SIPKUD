<?php

namespace App\Livewire\MasterData\Kecamatan;

use App\Models\Kecamatan;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Tambah Kecamatan'])]
class Create extends Component
{
    public string $nama_kecamatan = '';

    public string $kode_kecamatan = '';

    public string $status = 'aktif';

    public function mount(): void
    {
        Gate::authorize('kelola_kabupaten');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'nama_kecamatan' => ['required', 'string', 'max:255'],
            'kode_kecamatan' => ['required', 'string', 'max:50', 'unique:kecamatan,kode_kecamatan'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ], [
            'nama_kecamatan.required' => 'Nama kecamatan wajib diisi.',
            'kode_kecamatan.required' => 'Kode kecamatan wajib diisi.',
            'kode_kecamatan.unique' => 'Kode kecamatan sudah digunakan.',
            'status.required' => 'Status wajib dipilih.',
        ]);

        Kecamatan::create($validated);

        $this->dispatch('success', message: 'Kecamatan berhasil ditambahkan.');
        $this->redirect(route('kecamatan.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.master-data.kecamatan.create');
    }
}
