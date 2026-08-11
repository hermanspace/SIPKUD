<?php

namespace App\Livewire\Laporan;

use App\Models\Desa;
use App\Models\UnitUsaha;
use App\Services\AccountingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Neraca Saldo'])]
class NeracaSaldo extends Component
{
    public ?int $bulan = null;

    public ?int $tahun = null;

    public ?int $unitUsahaId = null;

    public ?int $selectedDesaId = null;

    protected $queryString = [
        'bulan' => ['except' => null],
        'tahun' => ['except' => null],
        'unitUsahaId' => ['except' => null],
        'selectedDesaId' => ['except' => null],
    ];

    public function mount(): void
    {
        Gate::authorize('view_desa_data');

        $user = Auth::user();

        // Set default bulan dan tahun
        if (! $this->bulan) {
            $this->bulan = (int) now()->format('m');
        }
        if (! $this->tahun) {
            $this->tahun = (int) now()->format('Y');
        }

        // Set default selectedDesaId untuk user yang punya desa_id
        if ($user->desa_id && ! $this->selectedDesaId) {
            $this->selectedDesaId = $user->desa_id;
        }

        // Untuk Super Admin dan Admin Kecamatan, set ke desa pertama yang dapat diakses
        if (! $this->selectedDesaId) {
            $accessibleDesas = $user->getAccessibleDesas();
            if ($accessibleDesas->isNotEmpty()) {
                $this->selectedDesaId = $accessibleDesas->first()->id;
            }
        }
    }

    public function exportPdf(AccountingService $accountingService)
    {
        $user = Auth::user();

        if (! $this->selectedDesaId || ! $user->canAccessDesa($this->selectedDesaId)) {
            abort(403, 'Anda tidak memiliki akses ke desa ini.');
        }

        // Format periode YYYY-MM
        $periode = sprintf('%04d-%02d', $this->tahun, $this->bulan);

        $neracaSaldo = $accountingService->getNeracaSaldoFromLedger(
            $this->selectedDesaId,
            $periode,
            $this->unitUsahaId
        );

        $periodeName = Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y');

        $unitUsaha = $this->unitUsahaId ? UnitUsaha::find($this->unitUsahaId) : null;
        $desa = Desa::find($this->selectedDesaId);

        // Hitung total
        $totalSaldoAwalDebit = collect($neracaSaldo)->sum('saldo_awal_debit');
        $totalSaldoAwalKredit = collect($neracaSaldo)->sum('saldo_awal_kredit');
        $totalMutasiDebit = collect($neracaSaldo)->sum('mutasi_debit');
        $totalMutasiKredit = collect($neracaSaldo)->sum('mutasi_kredit');
        $totalSaldoAkhirDebit = collect($neracaSaldo)->sum('saldo_akhir_debit');
        $totalSaldoAkhirKredit = collect($neracaSaldo)->sum('saldo_akhir_kredit');

        $pdf = Pdf::loadView('pdf.neraca-saldo', [
            'neracaSaldo' => $neracaSaldo,
            'periode' => $periodeName,
            'unitUsaha' => $unitUsaha,
            'desa' => $desa,
            'totalSaldoAwalDebit' => $totalSaldoAwalDebit,
            'totalSaldoAwalKredit' => $totalSaldoAwalKredit,
            'totalMutasiDebit' => $totalMutasiDebit,
            'totalMutasiKredit' => $totalMutasiKredit,
            'totalSaldoAkhirDebit' => $totalSaldoAkhirDebit,
            'totalSaldoAkhirKredit' => $totalSaldoAkhirKredit,
        ])->setPaper('a4', 'landscape');

        $fileName = 'neraca-saldo-'.$periode.'-'.now()->format('His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }

    public function render(AccountingService $accountingService)
    {
        $user = Auth::user();

        // Get list desa yang dapat diakses
        $accessibleDesas = $user->getAccessibleDesas();

        // Validasi selectedDesaId
        if (! $this->selectedDesaId || ! $user->canAccessDesa($this->selectedDesaId)) {
            return view('livewire.laporan.neraca-saldo', [
                'data' => [],
                'totalSaldoAwalDebit' => 0,
                'totalSaldoAwalKredit' => 0,
                'totalMutasiDebit' => 0,
                'totalMutasiKredit' => 0,
                'totalSaldoAkhirDebit' => 0,
                'totalSaldoAkhirKredit' => 0,
                'units' => collect([]),
                'desas' => $accessibleDesas,
                'error' => 'Silakan pilih desa untuk melihat laporan.',
            ]);
        }

        // Format periode YYYY-MM
        $periode = sprintf('%04d-%02d', $this->tahun, $this->bulan);

        // Get data dari ledger (neraca_saldo table)
        $data = $accountingService->getNeracaSaldoFromLedger(
            $this->selectedDesaId,
            $periode,
            $this->unitUsahaId
        );

        // Hitung total per kolom
        $totalSaldoAwalDebit = collect($data)->sum('saldo_awal_debit');
        $totalSaldoAwalKredit = collect($data)->sum('saldo_awal_kredit');
        $totalMutasiDebit = collect($data)->sum('mutasi_debit');
        $totalMutasiKredit = collect($data)->sum('mutasi_kredit');
        $totalSaldoAkhirDebit = collect($data)->sum('saldo_akhir_debit');
        $totalSaldoAkhirKredit = collect($data)->sum('saldo_akhir_kredit');

        // List unit usaha
        $units = UnitUsaha::where('desa_id', $this->selectedDesaId)
            ->aktif()
            ->orderBy('nama_unit')
            ->get();

        return view('livewire.laporan.neraca-saldo', [
            'data' => $data,
            'totalSaldoAwalDebit' => $totalSaldoAwalDebit,
            'totalSaldoAwalKredit' => $totalSaldoAwalKredit,
            'totalMutasiDebit' => $totalMutasiDebit,
            'totalMutasiKredit' => $totalMutasiKredit,
            'totalSaldoAkhirDebit' => $totalSaldoAkhirDebit,
            'totalSaldoAkhirKredit' => $totalSaldoAkhirKredit,
            'units' => $units,
            'desas' => $accessibleDesas,
            'periode' => Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y'),
        ]);
    }
}
