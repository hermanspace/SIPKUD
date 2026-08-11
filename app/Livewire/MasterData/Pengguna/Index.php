<?php

namespace App\Livewire\MasterData\Pengguna;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Master Pengguna'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public ?int $kecamatanFilter = null;

    public ?int $desaFilter = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
        'kecamatanFilter' => ['except' => null],
        'desaFilter' => ['except' => null],
    ];

    public function mount(): void
    {
        // Super Admin dan Admin Kecamatan dapat mengakses daftar pengguna
        $user = Auth::user();
        if (! $user) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        // Gate admin_kecamatan mencakup Super Admin, Admin Kabupaten, dan Admin Kecamatan
        Gate::authorize('admin_kecamatan');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingKecamatanFilter(): void
    {
        $this->resetPage();
        $this->reset('desaFilter');
    }

    public function updatingDesaFilter(): void
    {
        $this->resetPage();
    }

    public function delete(int $userId): void
    {
        $user = User::findOrFail($userId);

        // Prevent deleting yourself
        if ($user->id === Auth::id()) {
            $this->dispatch('error', message: 'Tidak dapat menghapus akun Anda sendiri.');

            return;
        }

        // Pemanggil harus berwenang atas akun target (Admin Kabupaten tidak
        // boleh menyentuh Super Admin; Admin Kecamatan hanya admin desa binaannya)
        if (! Auth::user()->canManageUser($user)) {
            $this->dispatch('error', message: 'Anda tidak berwenang menghapus pengguna ini.');

            return;
        }

        // Prevent deleting the last super admin
        if ($user->isSuperAdmin() && User::where('role', 'super_admin')->count() <= 1) {
            $this->dispatch('error', message: 'Tidak dapat menghapus super admin terakhir.');

            return;
        }

        $user->delete();
        $this->dispatch('success', message: 'Pengguna berhasil dihapus.');
    }

    public function render()
    {
        $user = Auth::user();
        if (! $user) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        $query = User::with(['kecamatan', 'desa'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nama', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            });

        // Jika Admin Kecamatan, hanya tampilkan admin desa di kecamatannya
        if ($user->isAdminKecamatan()) {
            $query->where('kecamatan_id', $user->kecamatan_id)
                ->where('role', 'admin_desa'); // Hanya admin desa di kecamatannya

            // Filter desa untuk admin kecamatan
            if ($this->desaFilter) {
                $query->where('desa_id', $this->desaFilter);
            }
        } else {
            // Admin Kabupaten tidak melihat akun Super Admin sama sekali
            if ($user->isAdminKabupaten()) {
                $query->where('role', '!=', 'super_admin');
            }

            // Filter untuk Super Admin / Admin Kabupaten
            $query->when($this->roleFilter, function ($query) {
                $query->where('role', $this->roleFilter);
            })
                ->when($this->kecamatanFilter, function ($query) {
                    $query->where('kecamatan_id', $this->kecamatanFilter);
                })
                ->when($this->desaFilter, function ($query) {
                    $query->where('desa_id', $this->desaFilter);
                });
        }

        $query->orderBy('nama');

        // Filter kecamatan untuk dropdown
        if ($user->isAdminKecamatan()) {
            $kecamatan = Kecamatan::where('id', $user->kecamatan_id)->get();
        } else {
            $kecamatan = Kecamatan::aktif()->orderBy('nama_kecamatan')->get();
        }

        // Filter desa untuk dropdown
        if ($user->isAdminKecamatan()) {
            $desa = Desa::where('kecamatan_id', $user->kecamatan_id)->aktif()->orderBy('nama_desa')->get();
        } else {
            $desa = $this->kecamatanFilter
                ? Desa::where('kecamatan_id', $this->kecamatanFilter)->aktif()->orderBy('nama_desa')->get()
                : collect();
        }

        return view('livewire.master-data.pengguna.index', [
            'users' => $query->paginate(10),
            'kecamatan' => $kecamatan,
            'desa' => $desa,
        ]);
    }
}
