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

#[Layout('components.layouts.app', ['title' => 'Neraca'])]
class Neraca extends Component
{
    public int $bulan;

    public int $tahun;

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

        $neraca = $accountingService->getNeracaFromLedger(
            $this->selectedDesaId,
            $periode,
            $this->unitUsahaId
        );

        $perubahanModal = $accountingService->getPerubahanModal(
            $this->selectedDesaId,
            $periode,
            $this->unitUsahaId
        );

        $periodeName = Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y');
        $unitUsaha = $this->unitUsahaId ? UnitUsaha::find($this->unitUsahaId) : null;
        $desa = Desa::find($this->selectedDesaId);

        $pdf = Pdf::loadView('pdf.neraca', [
            'neraca' => $neraca,
            'perubahanModal' => $perubahanModal,
            'periode' => $periodeName,
            'unitUsaha' => $unitUsaha,
            'desa' => $desa,
        ])->setPaper('a4', 'portrait');

        $fileName = 'neraca-'.$periode.'-'.now()->format('His').'.pdf';

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
            return view('livewire.laporan.neraca', [
                'data' => [
                    'aset' => [],
                    'kewajiban' => [],
                    'modal' => [],
                ],
                'units' => collect([]),
                'desas' => $accessibleDesas,
                'totalAset' => 0,
                'totalKewajiban' => 0,
                'totalModal' => 0,
                'totalKewajibanModal' => 0,
                'isBalanced' => false,
                'selisih' => 0,
                'perubahanModal' => [
                    'modal_awal' => 0,
                    'laba_bersih' => 0,
                    'prive' => 0,
                    'modal_akhir' => 0,
                    'detail_prive' => [],
                ],
                'error' => 'Silakan pilih desa untuk melihat laporan.',
            ]);
        }

        // Format periode YYYY-MM
        $periode = sprintf('%04d-%02d', $this->tahun, $this->bulan);

        // Get Neraca dari ledger
        $neraca = $accountingService->getNeracaFromLedger(
            $this->selectedDesaId,
            $periode,
            $this->unitUsahaId
        );

        // Get Perubahan Modal
        $perubahanModal = $accountingService->getPerubahanModal(
            $this->selectedDesaId,
            $periode,
            $this->unitUsahaId
        );

        // List unit usaha
        $units = UnitUsaha::where('desa_id', $this->selectedDesaId)
            ->aktif()
            ->orderBy('nama_unit')
            ->get();

        // Format data untuk view
        $data = [
            'aset' => $neraca['detail_aset'] ?? [],
            'kewajiban' => $neraca['detail_kewajiban'] ?? [],
            'modal' => $neraca['detail_modal'] ?? [],
        ];

        return view('livewire.laporan.neraca', [
            'data' => $data,
            'units' => $units,
            'desas' => $accessibleDesas,
            'totalAset' => $neraca['aset'] ?? 0,
            'totalKewajiban' => $neraca['kewajiban'] ?? 0,
            'totalModal' => $neraca['modal'] ?? 0,
            'totalKewajibanModal' => $neraca['total_kewajiban_modal'] ?? 0,
            'isBalanced' => $neraca['is_balanced'] ?? false,
            'selisih' => $neraca['selisih'] ?? 0,
            'perubahanModal' => $perubahanModal,
            'periode' => Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y'),
        ]);
    }
}
