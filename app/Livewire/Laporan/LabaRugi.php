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

#[Layout('components.layouts.app', ['title' => 'Laporan Laba Rugi'])]
class LabaRugi extends Component
{
    public int $bulan;

    public int $tahun;

    public ?int $unitUsahaId = null;

    public ?int $selectedDesaId = null;

    public string $mode = 'bulanan'; // 'bulanan' atau 'kumulatif'

    protected $queryString = [
        'bulan',
        'tahun',
        'unitUsahaId' => ['except' => null],
        'selectedDesaId' => ['except' => null],
        'mode' => ['except' => 'bulanan'],
    ];

    public function mount(): void
    {
        Gate::authorize('view_desa_data');

        $user = Auth::user();

        // Set default bulan dan tahun
        if (! isset($this->bulan)) {
            $this->bulan = (int) now()->format('m');
        }
        if (! isset($this->tahun)) {
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

        $labaRugi = $accountingService->getLabaRugiFromLedger(
            $this->selectedDesaId,
            $periode,
            $this->mode,
            $this->unitUsahaId
        );

        $periodeName = Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y');
        $unitUsaha = $this->unitUsahaId ? UnitUsaha::find($this->unitUsahaId) : null;
        $desa = Desa::find($this->selectedDesaId);

        $pdf = Pdf::loadView('pdf.laba-rugi', [
            'labaRugi' => $labaRugi,
            'periode' => $periodeName,
            'unitUsaha' => $unitUsaha,
            'desa' => $desa,
        ])->setPaper('a4', 'portrait');

        $fileName = 'laba-rugi-'.$this->mode.'-'.$periode.'-'.now()->format('His').'.pdf';

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
            return view('livewire.laporan.laba-rugi', [
                'data' => ['pendapatan' => [], 'beban' => []],
                'units' => collect([]),
                'desas' => $accessibleDesas,
                'totalPendapatan' => 0,
                'totalBeban' => 0,
                'labaRugi' => 0,
                'error' => 'Silakan pilih desa untuk melihat laporan.',
            ]);
        }

        // Format periode YYYY-MM
        $periode = sprintf('%04d-%02d', $this->tahun, $this->bulan);

        // Get data dari ledger (neraca_saldo table)
        $result = $accountingService->getLabaRugiFromLedger(
            $this->selectedDesaId,
            $periode,
            $this->mode,
            $this->unitUsahaId
        );

        // List unit usaha
        $units = UnitUsaha::where('desa_id', $this->selectedDesaId)
            ->aktif()
            ->orderBy('nama_unit')
            ->get();

        // Format data untuk view
        $data = [
            'pendapatan' => $result['detail_pendapatan'] ?? [],
            'beban' => $result['detail_beban'] ?? [],
        ];

        return view('livewire.laporan.laba-rugi', [
            'data' => $data,
            'units' => $units,
            'desas' => $accessibleDesas,
            'totalPendapatan' => $result['pendapatan'] ?? 0,
            'totalBeban' => $result['beban'] ?? 0,
            'labaBersih' => $result['laba_bersih'] ?? 0,
            'mode' => $result['mode'] ?? 'bulanan',
            'periode' => Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y'),
        ]);
    }
}
