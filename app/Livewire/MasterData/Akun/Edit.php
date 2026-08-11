<?php

namespace App\Livewire\MasterData\Akun;

use App\Models\Akun;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Edit Akun'])]
class Edit extends Component
{
    public Akun $akun;

    public string $kode_akun = '';

    public string $nama_akun = '';

    public string $tipe_akun = 'aset';

    public string $status = 'aktif';

    public function mount(Akun $akun): void
    {
        Gate::authorize('manage_akun');

        $this->akun = $akun;
        $this->kode_akun = $akun->kode_akun;
        $this->nama_akun = $akun->nama_akun;
        $this->tipe_akun = $akun->tipe_akun;
        $this->status = $akun->status;
    }

    public function update(): void
    {
        $validated = $this->validate([
            'kode_akun' => ['required', 'string', 'max:50', Rule::unique('akun')->ignore($this->akun->id)],
            'nama_akun' => ['required', 'string', 'max:255'],
            'tipe_akun' => ['required', 'in:aset,kewajiban,ekuitas,pendapatan,beban'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ], [
            'kode_akun.required' => 'Kode akun wajib diisi.',
            'kode_akun.unique' => 'Kode akun sudah digunakan.',
            'nama_akun.required' => 'Nama akun wajib diisi.',
            'tipe_akun.required' => 'Tipe akun wajib dipilih.',
            'tipe_akun.in' => 'Tipe akun tidak valid.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
        ]);

        $validated['updated_by'] = Auth::id();

        $this->akun->update($validated);

        $this->dispatch('success', message: 'Akun berhasil diperbarui.');
        $this->redirect(route('akun.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.master-data.akun.edit');
    }
}
