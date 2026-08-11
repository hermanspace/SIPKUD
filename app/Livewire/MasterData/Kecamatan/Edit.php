<?php

namespace App\Livewire\MasterData\Kecamatan;

use App\Models\Kecamatan;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Edit Kecamatan'])]
class Edit extends Component
{
    public Kecamatan $kecamatan;

    public string $nama_kecamatan = '';

    public string $kode_kecamatan = '';

    public string $status = 'aktif';

    public function mount(Kecamatan $kecamatan): void
    {
        Gate::authorize('kelola_kabupaten');

        $this->kecamatan = $kecamatan;
        $this->nama_kecamatan = $kecamatan->nama_kecamatan;
        $this->kode_kecamatan = $kecamatan->kode_kecamatan;
        $this->status = $kecamatan->status;
    }

    public function update(): void
    {
        $validated = $this->validate([
            'nama_kecamatan' => ['required', 'string', 'max:255'],
            'kode_kecamatan' => ['required', 'string', 'max:50', 'unique:kecamatan,kode_kecamatan,'.$this->kecamatan->id],
            'status' => ['required', 'in:aktif,nonaktif'],
        ], [
            'nama_kecamatan.required' => 'Nama kecamatan wajib diisi.',
            'kode_kecamatan.required' => 'Kode kecamatan wajib diisi.',
            'kode_kecamatan.unique' => 'Kode kecamatan sudah digunakan.',
            'status.required' => 'Status wajib dipilih.',
        ]);

        $this->kecamatan->update($validated);

        $this->dispatch('success', message: 'Kecamatan berhasil diperbarui.');
        $this->redirect(route('kecamatan.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.master-data.kecamatan.edit');
    }
}
