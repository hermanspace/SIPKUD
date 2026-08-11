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

#[Layout('components.layouts.app', ['title' => 'Laporan Perubahan Ekuitas'])]
class PerubahanEkuitas extends Component
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

        if (! isset($this->bulan)) {
            $this->bulan = (int) now()->format('m');
        }
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

    public function exportPdf(AccountingService $accountingService)
    {
        $user = Auth::user();

        if (! $this->selectedDesaId || ! $user->canAccessDesa($this->selectedDesaId)) {
            abort(403, 'Anda tidak memiliki akses ke desa ini.');
        }

        $periode = sprintf('%04d-%02d', $this->tahun, $this->bulan);

        $pdf = Pdf::loadView('pdf.perubahan-ekuitas', [
            'data' => $accountingService->getPerubahanModal($this->selectedDesaId, $periode, $this->unitUsahaId),
            'periode' => Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y'),
            'desa' => Desa::find($this->selectedDesaId),
            'unitUsaha' => $this->unitUsahaId ? UnitUsaha::find($this->unitUsahaId) : null,
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'perubahan-ekuitas-'.$periode.'.pdf'
        );
    }

    public function render(AccountingService $accountingService)
    {
        $user = Auth::user();
        $desas = $user->getAccessibleDesas();

        if (! $this->selectedDesaId || ! $user->canAccessDesa($this->selectedDesaId)) {
            return view('livewire.laporan.perubahan-ekuitas', [
                'data' => null,
                'desas' => $desas,
                'units' => collect([]),
                'error' => 'Silakan pilih desa untuk melihat laporan.',
            ]);
        }

        $periode = sprintf('%04d-%02d', $this->tahun, $this->bulan);

        return view('livewire.laporan.perubahan-ekuitas', [
            'data' => $accountingService->getPerubahanModal($this->selectedDesaId, $periode, $this->unitUsahaId),
            'desas' => $desas,
            'units' => UnitUsaha::where('desa_id', $this->selectedDesaId)->where('status', 'aktif')->get(),
            'error' => null,
        ]);
    }
}
