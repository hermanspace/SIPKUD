<?php

namespace App\Livewire\Laporan;

use App\Exports\LaporanAkhirUspExport;
use App\Models\AngsuranPinjaman;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Pinjaman;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app', ['title' => 'Laporan Akhir USP'])]
class LaporanAkhirUsp extends Component
{
    public ?int $bulan = null;

    public ?int $tahun = null;

    public ?int $kecamatan_id = null;

    public ?int $desa_id = null;

    protected $queryString = [
        'bulan' => ['except' => null],
        'tahun' => ['except' => null],
        'kecamatan_id' => ['except' => null],
        'desa_id' => ['except' => null],
    ];

    public function mount(): void
    {
        // Admin Desa, Admin Kecamatan, dan Super Admin bisa melihat laporan
        Gate::authorize('view_desa_data');

        $user = Auth::user();

        // Set default tahun ke tahun saat ini
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

    public function exportExcel(): StreamedResponse
    {
        $data = $this->getReportData();

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

        $export = new LaporanAkhirUspExport(
            $data,
            $this->bulan,
            $this->tahun,
            $kecamatanNama,
            $desaNama
        );

        $fileName = 'laporan-akhir-usp-'.now()->format('Y-m-d-His').'.xlsx';
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
        $data = $this->getReportData();

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

        $pdf = Pdf::loadView('pdf.laporan-akhir-usp', array_merge($data, [
            'periode' => $periode,
            'kecamatanNama' => $kecamatanNama,
            'desaNama' => $desaNama,
        ]));

        $fileName = 'laporan-akhir-usp-'.now()->format('Y-m-d-His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }

    protected function getReportData(): array
    {
        $user = Auth::user();

        // Query angsuran untuk pendapatan jasa dan denda
        $angsuranQuery = AngsuranPinjaman::query()
            ->join('pinjaman', 'angsuran_pinjaman.pinjaman_id', '=', 'pinjaman.id');

        // Query pinjaman untuk sisa pinjaman
        $pinjamanQuery = Pinjaman::query()->with('angsuran');

        // Filter berdasarkan desa_id atau kecamatan_id
        if ($this->desa_id) {
            $angsuranQuery->where('pinjaman.desa_id', $this->desa_id);
            $pinjamanQuery->where('desa_id', $this->desa_id);
        } elseif ($this->kecamatan_id) {
            $angsuranQuery->join('desa', 'pinjaman.desa_id', '=', 'desa.id')
                ->where('desa.kecamatan_id', $this->kecamatan_id);
            $pinjamanQuery->whereHas('desa', fn ($q) => $q->where('kecamatan_id', $this->kecamatan_id));
        } elseif ($user->isAdminKecamatan()) {
            $angsuranQuery->join('desa', 'pinjaman.desa_id', '=', 'desa.id')
                ->where('desa.kecamatan_id', $user->kecamatan_id);
            $pinjamanQuery->whereHas('desa', fn ($q) => $q->where('kecamatan_id', $user->kecamatan_id));
        } elseif ($user->isAdminDesa()) {
            $angsuranQuery->where('pinjaman.desa_id', $user->desa_id);
            $pinjamanQuery->where('desa_id', $user->desa_id);
        }

        // Filter berdasarkan periode
        if ($this->bulan && $this->tahun) {
            $angsuranQuery->whereMonth('angsuran_pinjaman.tanggal_bayar', $this->bulan)
                ->whereYear('angsuran_pinjaman.tanggal_bayar', $this->tahun);
        } elseif ($this->tahun) {
            $angsuranQuery->whereYear('angsuran_pinjaman.tanggal_bayar', $this->tahun);
        }

        // Hitung total pendapatan
        $totalPendapatanJasa = $angsuranQuery->sum('angsuran_pinjaman.jasa_dibayar');
        $totalPendapatanDenda = (clone $angsuranQuery)->sum('angsuran_pinjaman.denda_dibayar');
        $totalPendapatan = $totalPendapatanJasa + $totalPendapatanDenda;

        // Total beban dari jurnal (scope & periode yang sama).
        // SHU = laba bersih (pendapatan - beban), BUKAN persentase pendapatan.
        $desaIds = null;
        if ($this->desa_id) {
            $desaIds = [$this->desa_id];
        } elseif ($this->kecamatan_id) {
            $desaIds = Desa::where('kecamatan_id', $this->kecamatan_id)->pluck('id')->all();
        } elseif ($user->isAdminKecamatan()) {
            $desaIds = Desa::where('kecamatan_id', $user->kecamatan_id)->pluck('id')->all();
        } elseif ($user->isAdminDesa()) {
            $desaIds = [$user->desa_id];
        }

        $bebanQuery = DB::table('jurnal_detail')
            ->join('jurnal', 'jurnal.id', '=', 'jurnal_detail.jurnal_id')
            ->join('akun', 'akun.id', '=', 'jurnal_detail.akun_id')
            ->whereNull('jurnal.deleted_at')
            ->where('jurnal.status', 'posted')
            ->where('jurnal.jenis_jurnal', '!=', 'penutup')
            ->where('akun.tipe_akun', 'beban')
            ->when($desaIds, fn ($q) => $q->whereIn('jurnal.desa_id', $desaIds));

        if ($this->bulan && $this->tahun) {
            $bebanQuery->whereMonth('jurnal.tanggal_transaksi', $this->bulan)
                ->whereYear('jurnal.tanggal_transaksi', $this->tahun);
        } elseif ($this->tahun) {
            $bebanQuery->whereYear('jurnal.tanggal_transaksi', $this->tahun);
        }

        $totalBeban = (float) $bebanQuery
            ->selectRaw("COALESCE(SUM(CASE WHEN jurnal_detail.posisi = 'debit' THEN jurnal_detail.jumlah ELSE -jurnal_detail.jumlah END), 0) as total")
            ->value('total');

        $totalShu = $totalPendapatan - $totalBeban;

        // Simulasi alokasi SHU sesuai konfigurasi AD/ART (config/accounting.php)
        $alokasiShu = collect(config('accounting.alokasi_shu', []))->map(fn ($a) => [
            'nama' => $a['nama'],
            'persen' => $a['persen'],
            'jumlah' => max(0, $totalShu) * $a['persen'] / 100,
        ])->all();

        // Hitung sisa pinjaman
        $pinjamanAktif = $pinjamanQuery->where('status_pinjaman', 'aktif')->get();
        $totalSisaPinjaman = $pinjamanAktif->sum(function ($pinjaman) {
            return $pinjaman->sisa_pinjaman;
        });

        // Hitung pinjaman tersalurkan
        $pinjamanTersalurkanQuery = Pinjaman::query();

        if ($this->desa_id) {
            $pinjamanTersalurkanQuery->where('desa_id', $this->desa_id);
        } elseif ($this->kecamatan_id) {
            $pinjamanTersalurkanQuery->whereHas('desa', fn ($q) => $q->where('kecamatan_id', $this->kecamatan_id));
        } elseif ($user->isAdminKecamatan()) {
            $pinjamanTersalurkanQuery->whereHas('desa', fn ($q) => $q->where('kecamatan_id', $user->kecamatan_id));
        } elseif ($user->isAdminDesa()) {
            $pinjamanTersalurkanQuery->where('desa_id', $user->desa_id);
        }

        if ($this->bulan && $this->tahun) {
            $pinjamanTersalurkanQuery->whereMonth('tanggal_pinjaman', $this->bulan)
                ->whereYear('tanggal_pinjaman', $this->tahun);
        } elseif ($this->tahun) {
            $pinjamanTersalurkanQuery->whereYear('tanggal_pinjaman', $this->tahun);
        }

        $totalPinjamanTersalurkan = $pinjamanTersalurkanQuery->sum('jumlah_pinjaman');
        $totalPokokTerbayar = (clone $angsuranQuery)->sum('angsuran_pinjaman.pokok_dibayar');

        return [
            'totalPendapatanJasa' => $totalPendapatanJasa,
            'totalPendapatanDenda' => $totalPendapatanDenda,
            'totalPendapatan' => $totalPendapatan,
            'totalBeban' => $totalBeban,
            'totalShu' => $totalShu,
            'alokasiShu' => $alokasiShu,
            'totalSisaPinjaman' => $totalSisaPinjaman,
            'totalPinjamanTersalurkan' => $totalPinjamanTersalurkan,
            'totalPokokTerbayar' => $totalPokokTerbayar,
            'jumlahPinjamanAktif' => $pinjamanAktif->count(),
        ];
    }

    public function render()
    {
        $user = Auth::user();

        $reportData = $this->getReportData();

        // Generate list bulan untuk dropdown
        $bulanList = collect(range(1, 12))->map(function ($m) {
            return [
                'value' => $m,
                'label' => Carbon::create()->month($m)->translatedFormat('F'),
            ];
        });

        // Generate list tahun (3 tahun terakhir + tahun sekarang + 1 tahun ke depan)
        $tahunSekarang = now()->year;
        $tahunList = range($tahunSekarang - 3, $tahunSekarang + 1);

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

        return view('livewire.laporan.laporan-akhir-usp', array_merge($reportData, [
            'bulanList' => $bulanList,
            'tahunList' => $tahunList,
            'kecamatanList' => $kecamatanList,
            'desaList' => $desaList,
            'user' => $user,
        ]));
    }
}
