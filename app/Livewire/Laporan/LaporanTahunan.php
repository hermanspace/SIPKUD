<?php

namespace App\Livewire\Laporan;

use App\Models\Desa;
use App\Services\AccountingService;
use App\Services\KolektibilitasService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Paket Laporan Tahunan BUM Desa (PP 11/2021 Pasal 58): satu unduhan PDF
 * berisi Laba Rugi, Perubahan Ekuitas, Posisi Keuangan (Neraca), Arus Kas,
 * dan CALK untuk satu tahun buku - siap dibawa ke Musyawarah Desa.
 */
#[Layout('components.layouts.app', ['title' => 'Laporan Tahunan'])]
class LaporanTahunan extends Component
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
        $this->tahun ??= (int) now()->subYear()->format('Y');

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
        $periodeDes = sprintf('%04d-12', $this->tahun);

        return [
            'desa' => Desa::with('kecamatan')->find($desaId),
            'tahun' => $this->tahun,
            'labaRugi' => $accounting->getLabaRugiTahunan($desaId, $this->tahun),
            'perubahanModal' => $accounting->getPerubahanModal($desaId, $periodeDes, null),
            'neraca' => $accounting->getNeracaFromLedger($desaId, $periodeDes, null),
            'arusKas' => $accounting->getArusKas($desaId, $this->tahun),
            'neracaSaldo' => collect($accounting->getNeracaSaldoFromLedger($desaId, $periodeDes))
                ->filter(fn ($r) => abs($r['saldo_akhir_debit']) > 0.001 || abs($r['saldo_akhir_kredit']) > 0.001),
            'kolektibilitas' => $kolektibilitas->ringkasan($desaId),
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

        $pdf = Pdf::loadView('pdf.laporan-tahunan', $this->buildData($accounting, $kolektibilitas))
            ->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'laporan-tahunan-'.$this->tahun.'.pdf'
        );
    }

    public function render(AccountingService $accounting, KolektibilitasService $kolektibilitas)
    {
        $user = Auth::user();
        $desas = $user->getAccessibleDesas();

        if (! $this->selectedDesaId || ! $user->canAccessDesa($this->selectedDesaId)) {
            return view('livewire.laporan.laporan-tahunan', [
                'data' => null, 'desas' => $desas,
                'error' => 'Silakan pilih desa untuk melihat laporan.',
            ]);
        }

        return view('livewire.laporan.laporan-tahunan', [
            'data' => $this->buildData($accounting, $kolektibilitas),
            'desas' => $desas,
            'error' => null,
        ]);
    }
}
