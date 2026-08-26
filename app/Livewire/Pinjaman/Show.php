<?php

namespace App\Livewire\Pinjaman;

use App\Models\Pinjaman;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Kartu Pinjaman: detail satu pinjaman beserta riwayat angsurannya -
 * pengganti kartu Monitoring pada sistem Excel lama.
 */
#[Layout('components.layouts.app', ['title' => 'Detail Pinjaman'])]
class Show extends Component
{
    public Pinjaman $pinjaman;

    public function mount(Pinjaman $pinjaman): void
    {
        Gate::authorize('view_desa_data');

        if (! Auth::user()->canAccessDesa($pinjaman->desa_id)) {
            abort(403, 'Anda tidak memiliki akses ke pinjaman ini.');
        }

        $this->pinjaman = $pinjaman->load([
            'anggota',
            'sektorUsaha',
            'desa',
            'angsuran' => fn ($q) => $q->orderBy('angsuran_ke'),
        ]);
    }

    public function render()
    {
        return view('livewire.pinjaman.show');
    }
}
