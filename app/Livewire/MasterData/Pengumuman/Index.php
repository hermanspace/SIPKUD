<?php

namespace App\Livewire\MasterData\Pengumuman;

use App\Models\Pengumuman;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Master Pengumuman'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $tipeFilter = '';

    // Form properties
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $judul = '';

    public string $isi = '';

    public string $prioritas = 'sedang';

    public string $tipe = 'info';

    public bool $aktif = true;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'tipeFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        // Hanya Super Admin yang bisa akses
        Gate::authorize('viewAny', Pengumuman::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTipeFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $pengumuman = Pengumuman::findOrFail($id);

        $this->editingId = $pengumuman->id;
        $this->judul = $pengumuman->judul;
        $this->isi = $pengumuman->isi;
        $this->prioritas = $pengumuman->prioritas;
        $this->tipe = $pengumuman->tipe;
        $this->aktif = $pengumuman->aktif;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'prioritas' => 'required|in:rendah,sedang,tinggi',
            'tipe' => 'required|in:info,peringatan,penting',
            'aktif' => 'boolean',
        ], [
            'judul.required' => 'Judul harus diisi',
            'isi.required' => 'Isi pengumuman harus diisi',
        ]);

        $data = [
            'judul' => $this->judul,
            'isi' => $this->isi,
            'prioritas' => $this->prioritas,
            'tipe' => $this->tipe,
            'aktif' => $this->aktif,
        ];

        if ($this->editingId) {
            $pengumuman = Pengumuman::findOrFail($this->editingId);
            $pengumuman->update($data);
            session()->flash('message', 'Pengumuman berhasil diperbarui.');
        } else {
            $data['created_by'] = Auth::id();
            Pengumuman::create($data);
            session()->flash('message', 'Pengumuman berhasil dibuat.');
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        $pengumuman = Pengumuman::findOrFail($id);
        $pengumuman->delete();

        session()->flash('message', 'Pengumuman berhasil dihapus.');
    }

    public function toggleStatus(int $id): void
    {
        $pengumuman = Pengumuman::findOrFail($id);
        $pengumuman->update(['aktif' => ! $pengumuman->aktif]);

        session()->flash('message', 'Status pengumuman berhasil diubah.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->judul = '';
        $this->isi = '';
        $this->prioritas = 'sedang';
        $this->tipe = 'info';
        $this->aktif = true;
        $this->resetValidation();
    }

    public function render()
    {
        $query = Pengumuman::query()->with('creator');

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereLike('judul', '%'.$this->search.'%')
                    ->orWhereLike('isi', '%'.$this->search.'%');
            });
        }

        // Filter by status aktif
        if ($this->statusFilter !== '') {
            $query->where('aktif', $this->statusFilter === '1');
        }

        // Filter by tipe
        if ($this->tipeFilter) {
            $query->where('tipe', $this->tipeFilter);
        }

        $pengumuman = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('livewire.master-data.pengumuman.index', [
            'pengumuman' => $pengumuman,
        ]);
    }
}
