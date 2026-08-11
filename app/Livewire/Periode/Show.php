<?php

namespace App\Livewire\Periode;

use App\Models\NeracaSaldo;
use App\Models\UnitUsaha;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Detail Neraca Saldo Periode'])]
class Show extends Component
{
    public $desaId;

    public $periode;

    public $unitUsahaId = null;

    public function mount($desa_id, $periode): void
    {
        Gate::authorize('view_desa_data');

        $user = Auth::user();

        // Validasi akses desa
        if (! $user->canAccessDesa($desa_id)) {
            abort(403, 'Anda tidak memiliki akses ke desa ini.');
        }

        $this->desaId = $desa_id;
        $this->periode = $periode;
    }

    public function render()
    {
        $query = NeracaSaldo::with(['akun', 'unitUsaha'])
            ->where('desa_id', $this->desaId)
            ->where('periode', $this->periode)
            ->whereHas('akun'); // Hanya ambil yang memiliki akun yang masih ada

        if ($this->unitUsahaId) {
            $query->where('unit_usaha_id', $this->unitUsahaId);
        }

        $neracaSaldo = $query->orderBy('akun_id')->get();

        // Filter out items with null akun (safety check)
        $neracaSaldo = $neracaSaldo->filter(function ($item) {
            return $item->akun !== null;
        });

        // Group by tipe akun
        $grouped = $neracaSaldo->groupBy(function ($item) {
            return $item->akun?->tipe_akun ?? 'unknown';
        });

        // Calculate totals
        $totalSaldoAwalDebit = $neracaSaldo->sum('saldo_awal_debit');
        $totalSaldoAwalKredit = $neracaSaldo->sum('saldo_awal_kredit');
        $totalMutasiDebit = $neracaSaldo->sum('mutasi_debit');
        $totalMutasiKredit = $neracaSaldo->sum('mutasi_kredit');
        $totalSaldoAkhirDebit = $neracaSaldo->sum('saldo_akhir_debit');
        $totalSaldoAkhirKredit = $neracaSaldo->sum('saldo_akhir_kredit');

        $periodeName = Carbon::createFromFormat('Y-m', $this->periode)->translatedFormat('F Y');
        $statusPeriode = $neracaSaldo->first()?->status_periode ?? 'open';

        // Get units
        $units = UnitUsaha::where('desa_id', $this->desaId)
            ->aktif()
            ->orderBy('nama_unit')
            ->get();

        return view('livewire.periode.show', [
            'neracaSaldo' => $neracaSaldo,
            'grouped' => $grouped,
            'totalSaldoAwalDebit' => $totalSaldoAwalDebit,
            'totalSaldoAwalKredit' => $totalSaldoAwalKredit,
            'totalMutasiDebit' => $totalMutasiDebit,
            'totalMutasiKredit' => $totalMutasiKredit,
            'totalSaldoAkhirDebit' => $totalSaldoAkhirDebit,
            'totalSaldoAkhirKredit' => $totalSaldoAkhirKredit,
            'periodeName' => $periodeName,
            'statusPeriode' => $statusPeriode,
            'units' => $units,
        ]);
    }
}
