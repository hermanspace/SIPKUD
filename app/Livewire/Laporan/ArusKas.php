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

#[Layout('components.layouts.app', ['title' => 'Laporan Arus Kas'])]
class ArusKas extends Component
{
    public ?int $bulan = null; // null = satu tahun penuh

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

        if (! isset($this->tahun)) {
            $this->tahun = (int) now()->format('Y');
        }

        if ($user->desa_id && ! $this->selectedDesaId) {
            $this->selectedDesaId = $user->desa_id;
        }

        if (! $this->selectedDesaId) {
            $this->selectedDesaId = $user->getAccessibleDesas()->first()?->id;
        }
    }

    protected function periodeLabel(): string
    {
        return $this->bulan
            ? Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y')
            : "Tahun {$this->tahun}";
    }

    public function exportPdf(AccountingService $accountingService)
    {
        $user = Auth::user();

        if (! $this->selectedDesaId || ! $user->canAccessDesa($this->selectedDesaId)) {
            abort(403, 'Anda tidak memiliki akses ke desa ini.');
        }

        $data = $accountingService->getArusKas($this->selectedDesaId, $this->tahun, $this->bulan, $this->unitUsahaId);

        $pdf = Pdf::loadView('pdf.arus-kas', [
            'data' => $data,
            'periode' => $this->periodeLabel(),
            'desa' => Desa::find($this->selectedDesaId),
            'unitUsaha' => $this->unitUsahaId ? UnitUsaha::find($this->unitUsahaId) : null,
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'arus-kas-'.$this->tahun.($this->bulan ? '-'.$this->bulan : '').'.pdf'
        );
    }

    public function render(AccountingService $accountingService)
    {
        $user = Auth::user();
        $desas = $user->getAccessibleDesas();

        if (! $this->selectedDesaId || ! $user->canAccessDesa($this->selectedDesaId)) {
            return view('livewire.laporan.arus-kas', [
                'data' => null,
                'desas' => $desas,
                'units' => collect([]),
                'periodeLabel' => $this->periodeLabel(),
                'error' => 'Silakan pilih desa untuk melihat laporan.',
            ]);
        }

        return view('livewire.laporan.arus-kas', [
            'data' => $accountingService->getArusKas($this->selectedDesaId, $this->tahun, $this->bulan, $this->unitUsahaId),
            'desas' => $desas,
            'units' => UnitUsaha::where('desa_id', $this->selectedDesaId)->where('status', 'aktif')->get(),
            'periodeLabel' => $this->periodeLabel(),
            'error' => null,
        ]);
    }
}
