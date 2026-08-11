<?php

namespace App\Livewire\Pinjaman;

use App\Exports\PinjamanExport;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Pinjaman;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app', ['title' => 'Master Pinjaman'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $kecamatanFilter = null;

    public ?int $desaFilter = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'kecamatanFilter' => ['except' => null],
        'desaFilter' => ['except' => null],
    ];

    public function mount(): void
    {
        // Admin Desa dan Admin Kecamatan bisa melihat pinjaman
        // Admin Kecamatan hanya bisa melihat (read-only), tidak bisa create/edit/delete
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

    public function updatingKecamatanFilter(): void
    {
        $this->resetPage();
        $this->reset('desaFilter');
    }

    public function updatingDesaFilter(): void
    {
        $this->resetPage();
    }

    public function delete(int $pinjamanId): void
    {
        // Hanya Admin Desa yang bisa menghapus pinjaman
        $user = Auth::user();
        if (! $user || ! $user->isAdminDesa()) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus pinjaman.');
        }

        $pinjaman = Pinjaman::findOrFail($pinjamanId);

        // Catatan: Di fase selanjutnya, akan ada relasi ke modul Angsuran
        // Jika pinjaman memiliki angsuran, tidak boleh dihapus
        // Untuk sekarang, pinjaman bisa dihapus karena belum ada relasi ke modul lain

        $pinjaman->delete();
        $this->dispatch('success', message: 'Pinjaman berhasil dihapus.');
    }

    public function exportExcel(): StreamedResponse
    {
        $pinjaman = $this->getPinjamanQuery()->get();

        $kecamatanNama = null;
        $desaNama = null;

        if ($this->kecamatanFilter) {
            $kecamatan = Kecamatan::find($this->kecamatanFilter);
            $kecamatanNama = $kecamatan?->nama_kecamatan;
        }

        if ($this->desaFilter) {
            $desa = Desa::find($this->desaFilter);
            $desaNama = $desa?->nama_desa;
        }

        $export = new PinjamanExport(
            $pinjaman,
            $kecamatanNama,
            $desaNama,
            $this->statusFilter ?: null
        );

        $fileName = 'master-pinjaman-'.now()->format('Y-m-d-His').'.xlsx';
        $tempFile = storage_path('app/temp/'.$fileName);

        // Ensure temp directory exists
        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $export->export($tempFile);

        return response()->stream(function () use ($tempFile) {
            echo file_get_contents($tempFile);
            unlink($tempFile);
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    public function exportPdf()
    {
        $pinjaman = $this->getPinjamanQuery()->get();
        $user = Auth::user();

        $kecamatanNama = null;
        $desaNama = null;

        if ($this->kecamatanFilter) {
            $kecamatan = Kecamatan::find($this->kecamatanFilter);
            $kecamatanNama = $kecamatan?->nama_kecamatan;
        }

        if ($this->desaFilter) {
            $desa = Desa::find($this->desaFilter);
            $desaNama = $desa?->nama_desa;
        }

        $pdf = Pdf::loadView('pdf.master-pinjaman', [
            'pinjaman' => $pinjaman,
            'kecamatanNama' => $kecamatanNama,
            'desaNama' => $desaNama,
            'statusFilter' => $this->statusFilter ?: null,
            'user' => $user,
        ])->setPaper('a4', 'landscape');

        $fileName = 'master-pinjaman-'.now()->format('Y-m-d-His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }

    protected function getPinjamanQuery()
    {
        return Pinjaman::with(['anggota', 'sektorUsaha', 'desa.kecamatan'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nomor_pinjaman', 'like', '%'.$this->search.'%')
                        ->orWhereHas('anggota', function ($q) {
                            $q->where('nama', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status_pinjaman', $this->statusFilter);
            })
            ->when($this->kecamatanFilter, function ($query) {
                $query->whereHas('desa', function ($q) {
                    $q->where('kecamatan_id', $this->kecamatanFilter);
                });
            })
            ->when($this->desaFilter, function ($query) {
                $query->where('desa_id', $this->desaFilter);
            })
            ->orderBy('tanggal_pinjaman', 'desc')
            ->orderBy('nomor_pinjaman', 'desc');
    }

    public function render()
    {
        $user = Auth::user();

        $query = Pinjaman::with(['anggota', 'sektorUsaha', 'desa.kecamatan'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nomor_pinjaman', 'like', '%'.$this->search.'%')
                        ->orWhereHas('anggota', function ($q) {
                            $q->where('nama', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status_pinjaman', $this->statusFilter);
            })
            ->when($this->kecamatanFilter, function ($query) {
                $query->whereHas('desa', function ($q) {
                    $q->where('kecamatan_id', $this->kecamatanFilter);
                });
            })
            ->when($this->desaFilter, function ($query) {
                $query->where('desa_id', $this->desaFilter);
            })
            ->orderBy('tanggal_pinjaman', 'desc')
            ->orderBy('nomor_pinjaman', 'desc');

        // Get kecamatan and desa for filters
        $kecamatan = collect();
        $desa = collect();

        if ($user && $user->hasKabupatenScope()) {
            $kecamatan = Kecamatan::aktif()->orderBy('nama_kecamatan')->get();
            if ($this->kecamatanFilter) {
                $desa = Desa::where('kecamatan_id', $this->kecamatanFilter)
                    ->aktif()
                    ->orderBy('nama_desa')
                    ->get();
            }
        } elseif ($user && $user->isAdminKecamatan()) {
            // Admin kecamatan bisa filter berdasarkan desa di kecamatannya
            $desa = Desa::where('kecamatan_id', $user->kecamatan_id)
                ->aktif()
                ->orderBy('nama_desa')
                ->get();
        }

        return view('livewire.pinjaman.index', [
            'pinjaman' => $query->paginate(10),
            'kecamatan' => $kecamatan,
            'desa' => $desa,
        ]);
    }
}
