<?php

namespace App\Livewire\MasterData\SektorUsaha;

use App\Models\Desa;
use App\Models\SektorUsaha;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Master Sektor Usaha'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $desaFilter = null;

    /** Form tambah baru */
    public string $nama = '';

    public string $keterangan = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'desaFilter' => ['except' => null],
    ];

    public function mount(): void
    {
        Gate::authorize('view_desa_data');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDesaFilter(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdminDesa()) {
            abort(403, 'Hanya Admin Desa yang dapat menambah sektor usaha.');
        }

        $this->validate([
            'nama' => ['required', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'nama.required' => 'Nama sektor usaha wajib diisi.',
            'nama.max' => 'Nama maksimal 100 karakter.',
        ]);

        SektorUsaha::firstOrCreate(
            [
                'desa_id' => $user->desa_id,
                'nama' => trim($this->nama),
            ],
            [
                'keterangan' => trim($this->keterangan) ?: null,
                'status' => 'aktif',
            ]
        );

        $this->nama = '';
        $this->keterangan = '';
        $this->dispatch('success', message: 'Sektor usaha berhasil ditambahkan.');
    }

    public function delete(int $id): void
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdminDesa()) {
            abort(403, 'Anda tidak memiliki izin menghapus sektor usaha.');
        }

        $sektor = SektorUsaha::findOrFail($id);
        if (! $user->canAccessDesa($sektor->desa_id)) {
            abort(403);
        }

        $count = $sektor->pinjaman()->count();
        if ($count > 0) {
            $this->dispatch('error', message: "Tidak dapat menghapus. Sektor ini digunakan oleh {$count} pinjaman.");

            return;
        }

        $sektor->delete();
        $this->dispatch('success', message: 'Sektor usaha berhasil dihapus.');
    }

    public function render()
    {
        $user = Auth::user();

        $query = SektorUsaha::withCount('pinjaman')
            ->with('desa')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->whereLike('nama', '%'.$this->search.'%')
                    ->orWhereLike('keterangan', '%'.$this->search.'%');
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->desaFilter, fn ($q) => $q->where('desa_id', $this->desaFilter))
            ->orderBy('nama');

        $desa = collect();
        if ($user && $user->hasKabupatenScope()) {
            $desa = Desa::aktif()->orderBy('nama_desa')->get();
        } elseif ($user && $user->isAdminKecamatan()) {
            $desa = Desa::where('kecamatan_id', $user->kecamatan_id)->aktif()->orderBy('nama_desa')->get();
        }

        return view('livewire.master-data.sektor-usaha.index', [
            'sektorUsaha' => $query->paginate(15),
            'desa' => $desa,
        ]);
    }
}
