<?php

namespace App\Livewire\MasterData\Akun;

use App\Models\Akun;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Tambah Akun'])]
class Create extends Component
{
    public string $kode_akun = '';

    public string $nama_akun = '';

    public string $tipe_akun = 'aset';

    public string $status = 'aktif';

    public function mount(): void
    {
        Gate::authorize('manage_akun');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'kode_akun' => ['required', 'string', 'max:50', Rule::unique('akun')],
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

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        Akun::create($validated);

        $this->dispatch('success', message: 'Akun berhasil ditambahkan.');
        $this->redirect(route('akun.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.master-data.akun.create');
    }
}
