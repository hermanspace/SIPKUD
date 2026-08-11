<?php

namespace App\Livewire\MasterData\Anggota;

use App\Models\Anggota;
use App\Models\Kelompok;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Edit Anggota'])]
class Edit extends Component
{
    public Anggota $anggota;

    public string $nama = '';

    public string $nik = '';

    public ?string $alamat = null;

    public ?string $nomor_hp = null;

    public ?string $jenis_kelamin = null;

    public ?int $kelompok_id = null;

    public ?string $tanggal_gabung = null;

    public string $status = 'aktif';

    public function mount(Anggota $anggota): void
    {
        // Hanya Admin Desa yang bisa mengedit anggota
        Gate::authorize('admin_desa');

        $this->anggota = $anggota;
        $this->nama = $anggota->nama;
        $this->nik = $anggota->nik ?? '';
        $this->alamat = $anggota->alamat;
        $this->nomor_hp = $anggota->nomor_hp;
        $this->jenis_kelamin = $anggota->jenis_kelamin;
        $this->kelompok_id = $anggota->kelompok_id;
        $this->tanggal_gabung = $anggota->tanggal_gabung ? $anggota->tanggal_gabung->format('Y-m-d') : null;
        $this->status = $anggota->status;
    }

    public function update(): void
    {
        // Pastikan hanya admin desa yang bisa mengupdate anggota
        Gate::authorize('admin_desa');

        $validated = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'size:16', 'regex:/^[0-9]{16}$/', 'unique:anggota,nik,'.$this->anggota->id],
            'alamat' => ['nullable', 'string'],
            'nomor_hp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'kelompok_id' => ['nullable', 'exists:kelompok,id'],
            'tanggal_gabung' => ['required', 'date'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ], [
            'nama.required' => 'Nama anggota wajib diisi.',
            'nik.required' => 'NIK wajib diisi.',
            'nik.size' => 'NIK harus terdiri dari 16 digit.',
            'nik.regex' => 'NIK harus berupa angka 16 digit.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'nomor_hp.regex' => 'Nomor HP tidak valid.',
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid.',
            'kelompok_id.exists' => 'Kelompok tidak valid.',
            'tanggal_gabung.required' => 'Tanggal gabung wajib diisi.',
            'tanggal_gabung.date' => 'Tanggal gabung harus berupa tanggal yang valid.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
        ]);

        $validated['updated_by'] = Auth::id();
        $validated['tanggal_gabung'] = Carbon::parse($validated['tanggal_gabung']);

        $this->anggota->update($validated);

        $this->dispatch('success', message: 'Anggota berhasil diperbarui.');
        $this->redirect(route('anggota.index'), navigate: true);
    }

    public function render()
    {
        $kelompok = Kelompok::where('status', 'aktif')
            ->orderBy('nama_kelompok')
            ->get();

        return view('livewire.master-data.anggota.edit', [
            'kelompok' => $kelompok,
        ]);
    }
}
