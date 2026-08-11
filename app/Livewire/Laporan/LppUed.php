<?php

namespace App\Livewire\Laporan;

use App\Exports\LppUedExport;
use App\Models\Anggota;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Pinjaman;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app', ['title' => 'Laporan LPP UED'])]
class LppUed extends Component
{
    public ?int $bulan = null;

    public ?int $tahun = null;

    public ?int $kecamatan_id = null;

    public ?int $desa_id = null;

    // Modal detail anggota
    public bool $showDetailModal = false;

    public ?Anggota $selectedAnggota = null;

    public $anggotaPinjaman = [];

    protected $queryString = [
        'bulan' => ['except' => null],
        'tahun' => ['except' => null],
        'kecamatan_id' => ['except' => null],
        'desa_id' => ['except' => null],
    ];

    public function mount(): void
    {
        // Admin Desa dan Admin Kecamatan bisa melihat laporan
        Gate::authorize('view_desa_data');

        $user = Auth::user();

        // Set default bulan dan tahun ke bulan/tahun saat ini
        if (! $this->bulan) {
            $this->bulan = (int) now()->format('m');
        }
        if (! $this->tahun) {
            $this->tahun = (int) now()->format('Y');
        }

        // Set default filter berdasarkan role
        if ($user->isAdminKecamatan() && ! $this->kecamatan_id) {
            $this->kecamatan_id = $user->kecamatan_id;
        }
        if ($user->isAdminDesa()) {
            $this->kecamatan_id = $user->desa->kecamatan_id ?? null;
            $this->desa_id = $user->desa_id;
        }
    }

    public function updatedKecamatanId(): void
    {
        // Reset desa_id saat kecamatan berubah
        $this->desa_id = null;
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
        $laporan = $this->getReportData();

        $kecamatanNama = null;
        $desaNama = null;

        if ($this->kecamatan_id) {
            $kecamatan = Kecamatan::find($this->kecamatan_id);
            $kecamatanNama = $kecamatan?->nama_kecamatan;
        }

        if ($this->desa_id) {
            $desa = Desa::find($this->desa_id);
            $desaNama = $desa?->nama_desa;
        }

        $export = new LppUedExport(
            $laporan,
            $this->bulan,
            $this->tahun,
            $kecamatanNama,
            $desaNama
        );

        $fileName = 'lpp-ued-'.now()->format('Y-m-d-His').'.xlsx';
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
        $laporan = $this->getReportData();

        $kecamatanNama = null;
        $desaNama = null;

        if ($this->kecamatan_id) {
            $kecamatan = Kecamatan::find($this->kecamatan_id);
            $kecamatanNama = $kecamatan?->nama_kecamatan;
        }

        if ($this->desa_id) {
            $desa = Desa::find($this->desa_id);
            $desaNama = $desa?->nama_desa;
        }

        $periode = '';
        if ($this->bulan && $this->tahun) {
            $periode = Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y');
        } elseif ($this->tahun) {
            $periode = "Tahun {$this->tahun}";
        }

        $pdf = Pdf::loadView('pdf.lpp-ued', [
            'laporan' => $laporan,
            'periode' => $periode,
            'kecamatanNama' => $kecamatanNama,
            'desaNama' => $desaNama,
        ])->setPaper('a4', 'landscape');

        $fileName = 'lpp-ued-'.now()->format('Y-m-d-His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }

    protected function getReportData()
    {
        $user = Auth::user();

        // Query pinjaman dengan aggregasi angsuran
        $query = Pinjaman::with(['anggota', 'desa.kecamatan', 'angsuran'])
            ->when($this->bulan && $this->tahun, function ($q) {
                // Filter berdasarkan bulan dan tahun pinjaman
                $q->whereYear('tanggal_pinjaman', $this->tahun)
                    ->whereMonth('tanggal_pinjaman', $this->bulan);
            })
            ->when($this->desa_id, function ($q) {
                $q->where('desa_id', $this->desa_id);
            })
            ->when($this->kecamatan_id && ! $this->desa_id, function ($q) {
                $q->whereHas('desa', fn ($sq) => $sq->where('kecamatan_id', $this->kecamatan_id));
            })
            ->when(! $this->desa_id && ! $this->kecamatan_id && $user->isAdminKecamatan(), function ($q) use ($user) {
                $q->whereHas('desa', fn ($sq) => $sq->where('kecamatan_id', $user->kecamatan_id));
            })
            ->when(! $this->desa_id && ! $this->kecamatan_id && $user->isAdminDesa(), function ($q) use ($user) {
                $q->where('desa_id', $user->desa_id);
            })
            ->orderBy('tanggal_pinjaman', 'desc')
            ->orderBy('nomor_pinjaman', 'desc');

        $pinjaman = $query->get();

        // Transform data untuk laporan
        return $pinjaman->map(function ($p) {
            return [
                'anggota_id' => $p->anggota->id,
                'nomor_anggota' => $p->anggota->nik ?? '-',
                'nama_anggota' => $p->anggota->nama,
                'nomor_pinjaman' => $p->nomor_pinjaman,
                'jumlah_pinjaman' => (float) $p->jumlah_pinjaman,
                'total_angsuran_pokok' => $p->total_pokok_dibayar,
                'total_jasa' => $p->total_jasa_dibayar,
                'sisa_pinjaman' => $p->sisa_pinjaman,
                'status_pinjaman' => $p->status_pinjaman,
            ];
        });
    }

    public function render()
    {
        $user = Auth::user();

        $laporan = $this->getReportData();

        // Generate list bulan dan tahun untuk filter
        $bulanList = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulanList[$i] = Carbon::create(null, $i, 1)->locale('id')->translatedFormat('F');
        }

        $tahunList = [];
        $currentYear = (int) now()->format('Y');
        for ($i = $currentYear; $i >= $currentYear - 5; $i--) {
            $tahunList[$i] = $i;
        }

        // List kecamatan dan desa untuk filter
        $kecamatanList = [];
        $desaList = [];

        if ($user->hasKabupatenScope()) {
            $kecamatanList = Kecamatan::orderBy('nama_kecamatan')->get();
        } elseif ($user->isAdminKecamatan()) {
            $kecamatanList = Kecamatan::where('id', $user->kecamatan_id)->get();
        }

        if ($this->kecamatan_id) {
            $desaList = Desa::where('kecamatan_id', $this->kecamatan_id)->orderBy('nama_desa')->get();
        } elseif ($user->isAdminKecamatan()) {
            $desaList = Desa::where('kecamatan_id', $user->kecamatan_id)->orderBy('nama_desa')->get();
        }

        return view('livewire.laporan.lpp-ued', [
            'laporan' => $laporan,
            'bulanList' => $bulanList,
            'tahunList' => $tahunList,
            'kecamatanList' => $kecamatanList,
            'desaList' => $desaList,
            'user' => $user,
        ]);
    }
}
