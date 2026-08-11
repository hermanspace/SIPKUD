<?php

namespace App\Livewire\MasterData\Desa;

use App\Models\Desa;
use App\Models\Kecamatan;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Edit Desa'])]
class Edit extends Component
{
    public Desa $desa;

    public string $nama_desa = '';

    public string $kode_desa = '';

    public ?int $kecamatan_id = null;

    public string $status = 'aktif';

    public function mount(Desa $desa): void
    {
        Gate::authorize('kelola_kabupaten');

        $this->desa = $desa;
        $this->nama_desa = $desa->nama_desa;
        $this->kode_desa = $desa->kode_desa;
        $this->kecamatan_id = $desa->kecamatan_id;
        $this->status = $desa->status;
    }

    public function update(): void
    {
        $validated = $this->validate([
            'nama_desa' => ['required', 'string', 'max:255'],
            'kode_desa' => ['required', 'string', 'max:50', 'unique:desa,kode_desa,'.$this->desa->id],
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

        $this->desa->update($validated);

        $this->dispatch('success', message: 'Desa berhasil diperbarui.');
        $this->redirect(route('desa.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.master-data.desa.edit', [
            'kecamatan' => Kecamatan::aktif()->orderBy('nama_kecamatan')->get(),
        ]);
    }
}
