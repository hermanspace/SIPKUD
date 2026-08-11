<?php

namespace App\Livewire\Laporan;

use App\Exports\BukuKasExport;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\TransaksiKas;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app', ['title' => 'Buku Kas USP'])]
class BukuKas extends Component
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

        $export = new BukuKasExport(
            $data['transaksi'],
            $data['saldoAwal'],
            $this->bulan,
            $this->tahun,
            $kecamatanNama,
            $desaNama
        );

        $fileName = 'buku-kas-'.now()->format('Y-m-d-His').'.xlsx';
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

        $pdf = Pdf::loadView('pdf.buku-kas', [
            'transaksi' => $data['transaksi'],
            'saldoAwal' => $data['saldoAwal'],
            'periode' => $periode,
            'kecamatanNama' => $kecamatanNama,
            'desaNama' => $desaNama,
        ])->setPaper('a4', 'landscape');

        $fileName = 'buku-kas-'.now()->format('Y-m-d-His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }

    protected function getReportData(): array
    {
        $user = Auth::user();

        // Query transaksi kas berdasarkan filter
        $query = TransaksiKas::query()
            ->with(['pinjaman.anggota', 'angsuranPinjaman.pinjaman.anggota', 'desa.kecamatan']);

        // Filter berdasarkan desa_id atau kecamatan_id
        if ($this->desa_id) {
            $query->where('desa_id', $this->desa_id);
        } elseif ($this->kecamatan_id) {
            $query->whereHas('desa', fn ($q) => $q->where('kecamatan_id', $this->kecamatan_id));
        } elseif ($user->isAdminKecamatan()) {
            $query->whereHas('desa', fn ($q) => $q->where('kecamatan_id', $user->kecamatan_id));
        } elseif ($user->isAdminDesa()) {
            $query->where('desa_id', $user->desa_id);
        }

        // Filter bulan dan tahun
        if ($this->bulan && $this->tahun) {
            $query->whereMonth('tanggal_transaksi', $this->bulan)
                ->whereYear('tanggal_transaksi', $this->tahun);
        } elseif ($this->tahun) {
            $query->whereYear('tanggal_transaksi', $this->tahun);
        }

        // Ambil transaksi dan urutkan berdasarkan tanggal
        $transaksi = $query->orderBy('tanggal_transaksi')
            ->orderBy('id')
            ->get();

        // Hitung saldo awal
        $saldoAwalManualQuery = TransaksiKas::query()->where('jenis_transaksi', 'saldo_awal');

        if ($this->desa_id) {
            $saldoAwalManualQuery->where('desa_id', $this->desa_id);
        } elseif ($this->kecamatan_id) {
            $saldoAwalManualQuery->whereHas('desa', fn ($q) => $q->where('kecamatan_id', $this->kecamatan_id));
        } elseif ($user->isAdminKecamatan()) {
            $saldoAwalManualQuery->whereHas('desa', fn ($q) => $q->where('kecamatan_id', $user->kecamatan_id));
        } elseif ($user->isAdminDesa()) {
            $saldoAwalManualQuery->where('desa_id', $user->desa_id);
        }

        $saldoAwalManual = $saldoAwalManualQuery->first();

        // Hitung transaksi sebelum periode
        $saldoAwalQuery = TransaksiKas::query()->whereIn('jenis_transaksi', ['masuk', 'keluar']);

        if ($this->desa_id) {
            $saldoAwalQuery->where('desa_id', $this->desa_id);
        } elseif ($this->kecamatan_id) {
            $saldoAwalQuery->whereHas('desa', fn ($q) => $q->where('kecamatan_id', $this->kecamatan_id));
        } elseif ($user->isAdminKecamatan()) {
            $saldoAwalQuery->whereHas('desa', fn ($q) => $q->where('kecamatan_id', $user->kecamatan_id));
        } elseif ($user->isAdminDesa()) {
            $saldoAwalQuery->where('desa_id', $user->desa_id);
        }

        if ($this->bulan && $this->tahun) {
            $tanggalAwal = now()->setYear($this->tahun)->setMonth($this->bulan)->startOfMonth();
            $saldoAwalQuery->where('tanggal_transaksi', '<', $tanggalAwal);
        } elseif ($this->tahun) {
            $tanggalAwal = now()->setYear($this->tahun)->startOfYear();
            $saldoAwalQuery->where('tanggal_transaksi', '<', $tanggalAwal);
        }

        $saldoTransaksi = $saldoAwalQuery->sum(\DB::raw("CASE WHEN jenis_transaksi = 'masuk' THEN jumlah ELSE -jumlah END"));

        $saldoAwal = ($saldoAwalManual ? $saldoAwalManual->jumlah : 0) + $saldoTransaksi;

        // Hitung saldo berjalan
        $saldoBerjalan = $saldoAwal;
        $dataWithSaldo = $transaksi->map(function ($item) use (&$saldoBerjalan) {
            if ($item->jenis_transaksi === 'masuk') {
                $saldoBerjalan += $item->jumlah;
            } else {
                $saldoBerjalan -= $item->jumlah;
            }

            $item->saldo_berjalan = $saldoBerjalan;

            return $item;
        });

        return [
            'transaksi' => $dataWithSaldo,
            'saldoAwal' => $saldoAwal,
        ];
    }

    public function render()
    {
        $user = Auth::user();

        $reportData = $this->getReportData();
        $transaksi = $reportData['transaksi'];
        $saldoAwal = $reportData['saldoAwal'];

        // Get saldo awal manual
        $saldoAwalManualQuery = TransaksiKas::query()->where('jenis_transaksi', 'saldo_awal');

        if ($this->desa_id) {
            $saldoAwalManualQuery->where('desa_id', $this->desa_id);
        } elseif ($this->kecamatan_id) {
            $saldoAwalManualQuery->whereHas('desa', fn ($q) => $q->where('kecamatan_id', $this->kecamatan_id));
        } elseif ($user->isAdminKecamatan()) {
            $saldoAwalManualQuery->whereHas('desa', fn ($q) => $q->where('kecamatan_id', $user->kecamatan_id));
        } elseif ($user->isAdminDesa()) {
            $saldoAwalManualQuery->where('desa_id', $user->desa_id);
        }

        $saldoAwalManual = $saldoAwalManualQuery->first();

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

        return view('livewire.laporan.buku-kas', [
            'transaksi' => $transaksi,
            'saldoAwal' => $saldoAwal,
            'saldoAwalManual' => $saldoAwalManual,
            'bulanList' => $bulanList,
            'tahunList' => $tahunList,
            'kecamatanList' => $kecamatanList,
            'desaList' => $desaList,
            'user' => $user,
        ]);
    }
}
