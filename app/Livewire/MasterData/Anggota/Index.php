<?php

namespace App\Livewire\MasterData\Anggota;

use App\Exports\AnggotaExport;
use App\Models\Anggota;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Kelompok;
use App\Models\Pinjaman;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app', ['title' => 'Master Anggota'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $kelompokFilter = null;

    public string $statusFilter = '';

    public ?int $kecamatanFilter = null;

    public ?int $desaFilter = null;

    // Modal detail anggota
    public bool $showDetailModal = false;

    public ?Anggota $selectedAnggota = null;

    public $anggotaPinjaman = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'kelompokFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'kecamatanFilter' => ['except' => null],
        'desaFilter' => ['except' => null],
    ];

    public function mount(): void
    {
        // Admin Desa dan Admin Kecamatan bisa melihat anggota
        // Admin Kecamatan hanya bisa melihat (read-only), tidak bisa create/edit/delete
        Gate::authorize('view_desa_data');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingKelompokFilter(): void
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

    public function showAnggotaDetail(int $anggotaId): void
    {
        $this->selectedAnggota = Anggota::with(['kelompok', 'desa.kecamatan'])->find($anggotaId);

        if ($this->selectedAnggota) {
            // Ambil semua pinjaman anggota dengan relasi angsuran
            $this->anggotaPinjaman = Pinjaman::with('angsuran')
                ->where('anggota_id', $anggotaId)
                ->orderBy('tanggal_pinjaman', 'desc')
                ->get()
                ->map(function ($pinjaman) {
                    return [
                        'nomor_pinjaman' => $pinjaman->nomor_pinjaman,
                        'tanggal_pinjaman' => $pinjaman->tanggal_pinjaman,
                        'jumlah_pinjaman' => $pinjaman->jumlah_pinjaman,
                        'jangka_waktu' => $pinjaman->jangka_waktu,
                        'persentase_jasa' => $pinjaman->persentase_jasa,
                        'total_pokok_dibayar' => $pinjaman->total_pokok_dibayar,
                        'total_jasa_dibayar' => $pinjaman->total_jasa_dibayar,
                        'sisa_pinjaman' => $pinjaman->sisa_pinjaman,
                        'status_pinjaman' => $pinjaman->status_pinjaman,
                        'jumlah_angsuran' => $pinjaman->angsuran->count(),
                    ];
                })
                ->toArray();

            $this->showDetailModal = true;
        }
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedAnggota = null;
        $this->anggotaPinjaman = [];
    }

    public function exportAnggotaPdf()
    {
        if (! $this->selectedAnggota) {
            return;
        }

        $pdf = Pdf::loadView('pdf.detail-anggota', [
            'anggota' => $this->selectedAnggota,
            'pinjaman' => $this->anggotaPinjaman,
        ])->setPaper('a4', 'portrait');

        $fileName = 'detail-anggota-'.Str::slug($this->selectedAnggota->nama).'-'.now()->format('Y-m-d-His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }

    public function exportExcel(): StreamedResponse
    {
        $anggota = $this->getAnggotaQuery()->get();

        $kecamatanNama = null;
        $desaNama = null;
        $kelompokNama = null;

        if ($this->kecamatanFilter) {
            $kecamatan = Kecamatan::find($this->kecamatanFilter);
            $kecamatanNama = $kecamatan?->nama_kecamatan;
        }

        if ($this->desaFilter) {
            $desa = Desa::find($this->desaFilter);
            $desaNama = $desa?->nama_desa;
        }

        if ($this->kelompokFilter) {
            $kelompok = Kelompok::find($this->kelompokFilter);
            $kelompokNama = $kelompok?->nama_kelompok;
        }

        $export = new AnggotaExport(
            $anggota,
            $kecamatanNama,
            $desaNama,
            $kelompokNama,
            $this->statusFilter ?: null
        );

        $fileName = 'master-anggota-'.now()->format('Y-m-d-His').'.xlsx';
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
        $anggota = $this->getAnggotaQuery()->get();
        $user = Auth::user();

        $kecamatanNama = null;
        $desaNama = null;
        $kelompokNama = null;

        if ($this->kecamatanFilter) {
            $kecamatan = Kecamatan::find($this->kecamatanFilter);
            $kecamatanNama = $kecamatan?->nama_kecamatan;
        }

        if ($this->desaFilter) {
            $desa = Desa::find($this->desaFilter);
            $desaNama = $desa?->nama_desa;
        }

        if ($this->kelompokFilter) {
            $kelompok = Kelompok::find($this->kelompokFilter);
            $kelompokNama = $kelompok?->nama_kelompok;
        }

        $pdf = Pdf::loadView('pdf.master-anggota', [
            'anggota' => $anggota,
            'kecamatanNama' => $kecamatanNama,
            'desaNama' => $desaNama,
            'kelompokNama' => $kelompokNama,
            'statusFilter' => $this->statusFilter ?: null,
            'user' => $user,
        ])->setPaper('a4', 'landscape');

        $fileName = 'master-anggota-'.now()->format('Y-m-d-His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }

    protected function getAnggotaQuery()
    {
        $user = Auth::user();

        return Anggota::with(['kelompok', 'desa.kecamatan'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereLike('nama', '%'.$this->search.'%')
                        ->orWhereLike('alamat', '%'.$this->search.'%');
                });
            })
            ->when($this->kelompokFilter, function ($query) {
                $query->where('kelompok_id', $this->kelompokFilter);
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->kecamatanFilter, function ($query) {
                $query->whereHas('desa', function ($q) {
                    $q->where('kecamatan_id', $this->kecamatanFilter);
                });
            })
            ->when($this->desaFilter, function ($query) {
                $query->where('desa_id', $this->desaFilter);
            })
            ->orderBy('nama');
    }

    public function delete(int $anggotaId): void
    {
        // Hanya Admin Desa yang bisa menghapus anggota
        $user = Auth::user();
        if (! $user || ! $user->isAdminDesa()) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus anggota.');
        }

        $anggota = Anggota::findOrFail($anggotaId);

        // Catatan: Di fase selanjutnya, akan ada relasi ke modul Pinjaman
        // Jika anggota memiliki pinjaman aktif, tidak boleh dihapus
        // Untuk sekarang, anggota bisa dihapus karena belum ada relasi ke modul lain

        $anggota->delete();
        $this->dispatch('success', message: 'Anggota berhasil dihapus.');
    }

    public function render()
    {
        $user = Auth::user();

        $query = Anggota::with(['kelompok', 'desa.kecamatan'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereLike('nama', '%'.$this->search.'%')
                        ->orWhereLike('alamat', '%'.$this->search.'%');
                });
            })
            ->when($this->kelompokFilter, function ($query) {
                $query->where('kelompok_id', $this->kelompokFilter);
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->kecamatanFilter, function ($query) {
                $query->whereHas('desa', function ($q) {
                    $q->where('kecamatan_id', $this->kecamatanFilter);
                });
            })
            ->when($this->desaFilter, function ($query) {
                $query->where('desa_id', $this->desaFilter);
            })
            ->orderBy('nama');

        // Get kelompok for filter
        $kelompokQuery = Kelompok::where('status', 'aktif');
        if ($this->desaFilter) {
            $kelompokQuery->where('desa_id', $this->desaFilter);
        } elseif ($this->kecamatanFilter) {
            $kelompokQuery->whereHas('desa', function ($q) {
                $q->where('kecamatan_id', $this->kecamatanFilter);
            });
        } elseif ($user && $user->isAdminKecamatan()) {
            // Admin kecamatan hanya melihat kelompok di kecamatannya
            $kelompokQuery->whereHas('desa', function ($q) use ($user) {
                $q->where('kecamatan_id', $user->kecamatan_id);
            });
        }
        $kelompok = $kelompokQuery->orderBy('nama_kelompok')->get();

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

        return view('livewire.master-data.anggota.index', [
            'anggota' => $query->paginate(10),
            'kelompok' => $kelompok,
            'kecamatan' => $kecamatan,
            'desa' => $desa,
        ]);
    }
}
