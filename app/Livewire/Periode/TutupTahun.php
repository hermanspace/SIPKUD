<?php

namespace App\Livewire\Periode;

use App\Models\UnitUsaha;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Tutup Buku Tahunan'])]
class TutupTahun extends Component
{
    public int $tahun;

    public ?int $unitUsahaId = null;

    public string $confirmText = '';

    public function mount(): void
    {
        Gate::authorize('admin_desa');

        $this->tahun = (int) now()->subYear()->format('Y');
    }

    public function tutupTahun(AccountingService $accountingService): void
    {
        Gate::authorize('admin_desa');

        if ($this->confirmText !== 'TUTUP') {
            session()->flash('error', 'Ketik TUTUP (huruf besar) untuk mengonfirmasi.');

            return;
        }

        try {
            $result = $accountingService->closeYear(Auth::user()->desa_id, $this->tahun, $this->unitUsahaId);

            session()->flash('message', sprintf(
                'Tahun buku %d berhasil ditutup. Laba (SHU) bersih: Rp %s dipindahkan ke SHU Tahun Berjalan dan direklasifikasi ke SHU Tahun Lalu per 1 Januari %d.',
                $this->tahun,
                number_format($result['laba_bersih'], 2, ',', '.'),
                $this->tahun + 1
            ));
        } catch (ValidationException $e) {
            session()->flash('error', collect($e->errors())->flatten()->implode(' '));
        } finally {
            $this->confirmText = '';
        }
    }

    public function render(AccountingService $accountingService)
    {
        $desaId = Auth::user()->desa_id;

        // Pratinjau laba/rugi tahun terpilih + alokasi SHU
        $labaRugi = $accountingService->getLabaRugiTahunan($desaId, $this->tahun, $this->unitUsahaId);
        $sudahDitutup = $accountingService->isYearClosed($desaId, $this->tahun, $this->unitUsahaId);

        $alokasi = collect(config('accounting.alokasi_shu', []))->map(fn ($a) => [
            'nama' => $a['nama'],
            'persen' => $a['persen'],
            'jumlah' => max(0, $labaRugi['laba_bersih']) * $a['persen'] / 100,
        ]);

        return view('livewire.periode.tutup-tahun', [
            'labaRugi' => $labaRugi,
            'sudahDitutup' => $sudahDitutup,
            'alokasi' => $alokasi,
            'units' => UnitUsaha::where('desa_id', $desaId)->where('status', 'aktif')->get(),
        ]);
    }
}
