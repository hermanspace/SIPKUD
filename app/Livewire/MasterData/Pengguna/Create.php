<?php

namespace App\Livewire\MasterData\Pengguna;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Tambah Pengguna'])]
class Create extends Component
{
    public string $nama = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = 'admin_desa';

    public ?int $kecamatan_id = null;

    public ?int $desa_id = null;

    public function mount(): void
    {
        // Gate admin_kecamatan mencakup Super Admin, Admin Kabupaten, dan Admin Kecamatan
        Gate::authorize('admin_kecamatan');

        // Jika user adalah Admin Kecamatan, set default kecamatan_id dan role
        if (Auth::user()->isAdminKecamatan()) {
            $this->kecamatan_id = Auth::user()->kecamatan_id;
            $this->role = 'admin_desa'; // Admin Kecamatan hanya bisa membuat Admin Desa
        }
    }

    public function updatedRole(): void
    {
        // Role tingkat kabupaten tidak memiliki penempatan kecamatan/desa
        if (in_array($this->role, ['super_admin', 'admin_kabupaten'])) {
            $this->kecamatan_id = null;
            $this->desa_id = null;
        } elseif ($this->role === 'admin_kecamatan') {
            // Admin Kecamatan tidak memiliki desa_id
            $this->desa_id = null;
        }

        // Tolak role di luar kewenangan pembuat (server-side, bukan cuma UI)
        if (! in_array($this->role, Auth::user()->manageableRoles())) {
            $this->role = 'admin_desa';
            $this->dispatch('error', message: 'Anda tidak berwenang membuat pengguna dengan role tersebut.');
        }
    }

    public function updatedKecamatanId(): void
    {
        // Reset desa when kecamatan changes
        $this->desa_id = null;
    }

    public function save(): void
    {
        $user = Auth::user();

        // Validasi role berdasarkan kewenangan user yang membuat
        $allowedRoles = $user->manageableRoles();

        $validated = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:'.implode(',', $allowedRoles)],
            'kecamatan_id' => [
                'nullable',
                'required_if:role,admin_kecamatan,admin_desa,executive_view',
                'exists:kecamatan,id',
            ],
            'desa_id' => [
                'nullable',
                'required_if:role,admin_desa,executive_view',
                'exists:desa,id',
                function ($attribute, $value, $fail) {
                    if (! in_array($this->role, ['super_admin', 'admin_kabupaten', 'admin_kecamatan']) && $value && $this->kecamatan_id) {
                        $desa = Desa::find($value);
                        if ($desa && $desa->kecamatan_id !== $this->kecamatan_id) {
                            $fail('Desa harus berada di kecamatan yang dipilih.');
                        }
                    }
                },
            ],
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak diizinkan.',
            'kecamatan_id.required_if' => 'Kecamatan wajib dipilih untuk role ini.',
            'kecamatan_id.exists' => 'Kecamatan yang dipilih tidak valid.',
            'desa_id.required_if' => 'Desa wajib dipilih untuk role ini.',
            'desa_id.exists' => 'Desa yang dipilih tidak valid.',
        ]);

        // Jika user adalah Admin Kecamatan, pastikan mereka hanya membuat admin_desa di kecamatannya
        if ($user->isAdminKecamatan()) {
            if ($validated['role'] !== 'admin_desa') {
                $this->dispatch('error', message: 'Anda hanya dapat membuat Admin Desa.');

                return;
            }
            if ($validated['kecamatan_id'] !== $user->kecamatan_id) {
                $this->dispatch('error', message: 'Anda hanya dapat membuat Admin Desa di kecamatan Anda.');

                return;
            }
        }

        // Remove password_confirmation and hash password
        unset($validated['password_confirmation']);
        $validated['password'] = Hash::make($validated['password']);

        // Role tingkat kabupaten tidak memiliki penempatan kecamatan/desa
        if (in_array($validated['role'], ['super_admin', 'admin_kabupaten'])) {
            $validated['kecamatan_id'] = null;
            $validated['desa_id'] = null;
        }

        // Set desa_id to null for admin_kecamatan
        if ($validated['role'] === 'admin_kecamatan') {
            $validated['desa_id'] = null;
        }

        User::create($validated);

        $this->dispatch('success', message: 'Pengguna berhasil ditambahkan.');
        $this->redirect(route('pengguna.index'), navigate: true);
    }

    public function render()
    {
        $user = Auth::user();

        // Jika Admin Kecamatan, hanya tampilkan kecamatan mereka
        if ($user->isAdminKecamatan()) {
            $kecamatan = Kecamatan::where('id', $user->kecamatan_id)->get();
        } else {
            $kecamatan = Kecamatan::aktif()->orderBy('nama_kecamatan')->get();
        }

        $desa = $this->kecamatan_id
            ? Desa::where('kecamatan_id', $this->kecamatan_id)->aktif()->orderBy('nama_desa')->get()
            : collect();

        return view('livewire.master-data.pengguna.create', [
            'kecamatan' => $kecamatan,
            'desa' => $desa,
            'isAdminKecamatan' => $user->isAdminKecamatan(),
        ]);
    }
}
