<?php

namespace App\Livewire\MasterData\Anggota;

use App\Models\Anggota;
use App\Models\Kelompok;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Tambah Anggota'])]
class Create extends Component
{
    public string $nama = '';

    public string $nik = '';

    public ?string $alamat = null;

    public ?string $nomor_hp = null;

    public ?string $jenis_kelamin = null;

    public ?int $kelompok_id = null;

    public ?string $tanggal_gabung = null;

    public string $status = 'aktif';

    public function mount(): void
    {
        // Hanya Admin Desa yang bisa membuat anggota
        Gate::authorize('admin_desa');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'size:16', 'regex:/^[0-9]{16}$/', 'unique:anggota,nik'],
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

        // Pastikan hanya admin desa yang bisa membuat anggota
        Gate::authorize('admin_desa');

        $user = Auth::user();
        // Admin kecamatan tidak memiliki desa_id, jadi tidak bisa membuat anggota
        if (! $user->desa_id) {
            abort(403, 'Anda tidak memiliki izin untuk membuat anggota.');
        }

        $validated['desa_id'] = Auth::user()->desa_id;
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();
        $validated['tanggal_gabung'] = Carbon::parse($validated['tanggal_gabung']);

        Anggota::create($validated);

        $this->dispatch('success', message: 'Anggota berhasil ditambahkan.');
        $this->redirect(route('anggota.index'), navigate: true);
    }

    public function render()
    {
        $kelompok = Kelompok::where('status', 'aktif')
            ->orderBy('nama_kelompok')
            ->get();

        return view('livewire.master-data.anggota.create', [
            'kelompok' => $kelompok,
        ]);
    }
}
