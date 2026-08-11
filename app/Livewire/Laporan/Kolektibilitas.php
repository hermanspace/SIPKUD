<?php

namespace App\Livewire\Laporan;

use App\Services\KolektibilitasService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Kolektibilitas Pinjaman'])]
class Kolektibilitas extends Component
{
    public ?int $selectedDesaId = null;

    protected $queryString = [
        'selectedDesaId' => ['except' => null],
    ];

    public function mount(): void
    {
        Gate::authorize('view_desa_data');

        $user = Auth::user();

        if ($user->desa_id && ! $this->selectedDesaId) {
            $this->selectedDesaId = $user->desa_id;
        }

        if (! $this->selectedDesaId) {
            $this->selectedDesaId = $user->getAccessibleDesas()->first()?->id;
        }
    }

    /**
     * Buat jurnal penyesuaian penyisihan piutang (khusus Admin Desa).
     */
    public function buatPenyisihan(KolektibilitasService $service): void
    {
        Gate::authorize('admin_desa');

        try {
            $result = $service->buatJurnalPenyisihan(Auth::user()->desa_id);

            if ($result['jurnal'] === null) {
                session()->flash('message', sprintf(
                    'Cadangan kerugian piutang sudah sesuai target (Rp %s) - tidak ada penyesuaian.',
                    number_format($result['target'], 2, ',', '.')
                ));
            } else {
                session()->flash('message', sprintf(
                    'Jurnal penyisihan dibuat: penyesuaian Rp %s (target Rp %s, saldo sebelumnya Rp %s).',
                    number_format($result['penyesuaian'], 2, ',', '.'),
                    number_format($result['target'], 2, ',', '.'),
                    number_format($result['saldo_sebelum'], 2, ',', '.')
                ));
            }
        } catch (ValidationException $e) {
            session()->flash('error', collect($e->errors())->flatten()->implode(' '));
        }
    }

    public function render(KolektibilitasService $service)
    {
        $user = Auth::user();
        $desas = $user->getAccessibleDesas();

        if (! $this->selectedDesaId || ! $user->canAccessDesa($this->selectedDesaId)) {
            return view('livewire.laporan.kolektibilitas', [
                'data' => null,
                'desas' => $desas,
                'error' => 'Silakan pilih desa untuk melihat laporan.',
            ]);
        }

        return view('livewire.laporan.kolektibilitas', [
            'data' => $service->ringkasan($this->selectedDesaId),
            'desas' => $desas,
            'error' => null,
        ]);
    }
}
