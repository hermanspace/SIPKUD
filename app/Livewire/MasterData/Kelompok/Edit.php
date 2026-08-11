<?php

namespace App\Livewire\MasterData\Kelompok;

use App\Models\Kelompok;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Edit Kelompok'])]
class Edit extends Component
{
    public Kelompok $kelompok;

    public string $nama_kelompok = '';

    public ?string $keterangan = null;

    public string $status = 'aktif';

    public function mount(Kelompok $kelompok): void
    {
        // Hanya Admin Desa yang bisa mengedit kelompok
        Gate::authorize('admin_desa');

        $this->kelompok = $kelompok;
        $this->nama_kelompok = $kelompok->nama_kelompok;
        $this->keterangan = $kelompok->keterangan;
        $this->status = $kelompok->status;
    }

    public function update(): void
    {
        // Pastikan hanya admin desa yang bisa mengupdate kelompok
        Gate::authorize('admin_desa');

        $validated = $this->validate([
            'nama_kelompok' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ], [
            'nama_kelompok.required' => 'Nama kelompok wajib diisi.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
        ]);

        $validated['updated_by'] = Auth::id();

        $this->kelompok->update($validated);

        $this->dispatch('success', message: 'Kelompok berhasil diperbarui.');
        $this->redirect(route('kelompok.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.master-data.kelompok.edit');
    }
}
