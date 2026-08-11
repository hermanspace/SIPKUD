<?php

namespace App\Livewire\Periode;

use App\Models\NeracaSaldo;
use App\Models\UnitUsaha;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Manajemen Periode Akuntansi'])]
class Index extends Component
{
    public ?int $selectedDesaId = null;

    public ?int $selectedUnitUsahaId = null;

    public $tahun;

    public function mount(): void
    {
        Gate::authorize('view_desa_data');

        $user = Auth::user();

        // Set default tahun
        $this->tahun = now()->year;

        // Set default selectedDesaId
        if ($user->desa_id && ! $this->selectedDesaId) {
            $this->selectedDesaId = $user->desa_id;
        }

        if (! $this->selectedDesaId) {
            $accessibleDesas = $user->getAccessibleDesas();
            if ($accessibleDesas->isNotEmpty()) {
                $this->selectedDesaId = $accessibleDesas->first()->id;
            }
        }
    }

    public function closePeriod($periode): void
    {
        $user = Auth::user();

        // Hanya Admin Desa yang boleh close
        if (! $user->isAdminDesa()) {
            $this->dispatch('error', message: 'Anda tidak memiliki akses untuk menutup periode.');

            return;
        }

        try {
            $accountingService = app(AccountingService::class);
            $accountingService->closePeriod(
                $this->selectedDesaId,
                $periode,
                $this->selectedUnitUsahaId
            );

            $this->dispatch('success', message: "Periode {$periode} berhasil ditutup.");
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function reopenPeriod($periode): void
    {
        $user = Auth::user();

        // Hanya Admin Desa yang boleh reopen
        if (! $user->isAdminDesa()) {
            $this->dispatch('error', message: 'Anda tidak memiliki akses untuk membuka kembali periode.');

            return;
        }

        try {
            $accountingService = app(AccountingService::class);
            $accountingService->reopenPeriod(
                $this->selectedDesaId,
                $periode,
                $this->selectedUnitUsahaId
            );

            $this->dispatch('success', message: "Periode {$periode} berhasil dibuka kembali.");
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function recalculate($periode): void
    {
        $user = Auth::user();

        // Hanya Admin Desa yang boleh recalculate
        if (! $user->isAdminDesa()) {
            $this->dispatch('error', message: 'Anda tidak memiliki akses untuk recalculate periode.');

            return;
        }

        try {
            $accountingService = app(AccountingService::class);
            $accountingService->recalculateBalance(
                $this->selectedDesaId,
                $periode,
                $this->selectedUnitUsahaId
            );

            $this->dispatch('success', message: "Periode {$periode} berhasil di-recalculate.");
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function render()
    {
        $user = Auth::user();
        $accessibleDesas = $user->getAccessibleDesas();

        if (! $this->selectedDesaId || ! $user->canAccessDesa($this->selectedDesaId)) {
            return view('livewire.periode.index', [
                'periodes' => collect([]),
                'desas' => $accessibleDesas,
                'units' => collect([]),
                'error' => 'Silakan pilih desa untuk melihat periode akuntansi.',
            ]);
        }

        // Get summary per periode (bulanan) untuk tahun terpilih
        $periodes = collect(range(1, 12))->map(function ($bulan) {
            $periode = sprintf('%d-%02d', $this->tahun, $bulan);

            $query = NeracaSaldo::where('desa_id', $this->selectedDesaId)
                ->where('periode', $periode);

            if ($this->selectedUnitUsahaId) {
                $query->where('unit_usaha_id', $this->selectedUnitUsahaId);
            }

            $records = $query->get();

            $statusPeriode = $records->first()->status_periode ?? 'open';
            $closedAt = $records->first()->closed_at ?? null;

            return [
                'periode' => $periode,
                'bulan_nama' => Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y'),
                'jumlah_akun' => $records->count(),
                'total_debit' => $records->sum('saldo_akhir_debit'),
                'total_kredit' => $records->sum('saldo_akhir_kredit'),
                'status' => $statusPeriode,
                'closed_at' => $closedAt,
                'has_data' => $records->isNotEmpty(),
            ];
        });

        // Get units
        $units = UnitUsaha::where('desa_id', $this->selectedDesaId)
            ->aktif()
            ->orderBy('nama_unit')
            ->get();

        return view('livewire.periode.index', [
            'periodes' => $periodes,
            'desas' => $accessibleDesas,
            'units' => $units,
        ]);
    }
}
