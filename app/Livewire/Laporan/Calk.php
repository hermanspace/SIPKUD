<?php

namespace App\Livewire\Laporan;

use App\Models\Desa;
use App\Models\Pinjaman;
use App\Models\UnitUsaha;
use App\Services\AccountingService;
use App\Services\KolektibilitasService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Catatan atas Laporan Keuangan (CALK)'])]
class Calk extends Component
{
    public int $tahun;

    public ?int $selectedDesaId = null;

    protected $queryString = [
        'tahun' => ['except' => null],
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

    protected function buildData(AccountingService $accounting, KolektibilitasService $kolektibilitas): array
    {
        $desaId = $this->selectedDesaId;
        $periode = sprintf('%04d-12', $this->tahun);

        return [
            'desa' => Desa::with('kecamatan')->find($desaId),
            'tahun' => $this->tahun,
            'units' => UnitUsaha::where('desa_id', $desaId)->where('status', 'aktif')->get(),
            'neracaSaldo' => collect($accounting->getNeracaSaldoFromLedger($desaId, $periode))
                ->filter(fn ($r) => abs($r['saldo_akhir_debit']) > 0.001 || abs($r['saldo_akhir_kredit']) > 0.001),
            'labaRugi' => $accounting->getLabaRugiTahunan($desaId, $this->tahun),
            'kolektibilitas' => $kolektibilitas->ringkasan($desaId),
            'jumlahPinjamanAktif' => Pinjaman::withoutGlobalScopes()
                ->where('desa_id', $desaId)->where('status_pinjaman', 'aktif')->count(),
            'alokasiShu' => config('accounting.alokasi_shu', []),
            'tahunDitutup' => $accounting->isYearClosed($desaId, $this->tahun),
        ];
    }

    public function exportPdf(AccountingService $accounting, KolektibilitasService $kolektibilitas)
    {
        $user = Auth::user();

        if (! $this->selectedDesaId || ! $user->canAccessDesa($this->selectedDesaId)) {
            abort(403, 'Anda tidak memiliki akses ke desa ini.');
        }

        $pdf = Pdf::loadView('pdf.calk', $this->buildData($accounting, $kolektibilitas))
            ->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'calk-'.$this->tahun.'.pdf'
        );
    }

    public function render(AccountingService $accounting, KolektibilitasService $kolektibilitas)
    {
        $user = Auth::user();
        $desas = $user->getAccessibleDesas();

        if (! $this->selectedDesaId || ! $user->canAccessDesa($this->selectedDesaId)) {
            return view('livewire.laporan.calk', [
                'data' => null,
                'desas' => $desas,
                'error' => 'Silakan pilih desa untuk melihat laporan.',
            ]);
        }

        return view('livewire.laporan.calk', [
            'data' => $this->buildData($accounting, $kolektibilitas),
            'desas' => $desas,
            'error' => null,
        ]);
    }
}
