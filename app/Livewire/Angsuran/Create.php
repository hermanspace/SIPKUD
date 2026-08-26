<?php

namespace App\Livewire\Angsuran;

use App\Models\AngsuranPinjaman;
use App\Models\Pinjaman;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Tambah Angsuran'])]
class Create extends Component
{
    public ?int $pinjaman_id = null;

    public string $tanggal_bayar = '';

    public string $angsuran_ke = '';

    public string $pokok_dibayar = '';

    public string $jasa_dibayar = '';

    public string $denda_dibayar = '0';

    public string $total_dibayar = '0';

    public function mount(): void
    {
        // Hanya Admin Desa yang bisa membuat angsuran
        Gate::authorize('admin_desa');

        // Set default tanggal ke hari ini
        $this->tanggal_bayar = now()->format('Y-m-d');

        // Prefill dari tombol "Bayar Angsuran" di halaman detail pinjaman
        $pinjamanId = (int) request()->query('pinjaman');
        if ($pinjamanId > 0) {
            $adaDiDesa = Pinjaman::aktif()
                ->where('id', $pinjamanId)
                ->where('desa_id', Auth::user()->desa_id)
                ->exists();
            if ($adaDiDesa) {
                $this->pinjaman_id = $pinjamanId;
            }
        }
    }

    public function updatedPokokDibayar(): void
    {
        $this->calculateTotal();
    }

    public function updatedJasaDibayar(): void
    {
        $this->calculateTotal();
    }

    public function updatedDendaDibayar(): void
    {
        $this->calculateTotal();
    }

    public function calculateTotal(): void
    {
        $pokok = (float) ($this->pokok_dibayar ?: 0);
        $jasa = (float) ($this->jasa_dibayar ?: 0);
        $denda = (float) ($this->denda_dibayar ?: 0);

        $this->total_dibayar = (string) ($pokok + $jasa + $denda);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'pinjaman_id' => ['required', 'exists:pinjaman,id'],
            'tanggal_bayar' => ['required', 'date'],
            'angsuran_ke' => ['required', 'integer', 'min:1'],
            'pokok_dibayar' => ['required', 'numeric', 'min:0'],
            'jasa_dibayar' => ['required', 'numeric', 'min:0'],
            'denda_dibayar' => ['required', 'numeric', 'min:0'],
        ], [
            'pinjaman_id.required' => 'Pinjaman wajib dipilih.',
            'pinjaman_id.exists' => 'Pinjaman yang dipilih tidak valid.',
            'tanggal_bayar.required' => 'Tanggal bayar wajib diisi.',
            'tanggal_bayar.date' => 'Tanggal bayar harus berupa tanggal yang valid.',
            'angsuran_ke.required' => 'Angsuran ke wajib diisi.',
            'angsuran_ke.integer' => 'Angsuran ke harus berupa bilangan bulat.',
            'angsuran_ke.min' => 'Angsuran ke harus minimal 1.',
            'pokok_dibayar.required' => 'Pokok dibayar wajib diisi.',
            'pokok_dibayar.numeric' => 'Pokok dibayar harus berupa angka.',
            'pokok_dibayar.min' => 'Pokok dibayar tidak boleh negatif.',
            'jasa_dibayar.required' => 'Jasa dibayar wajib diisi.',
            'jasa_dibayar.numeric' => 'Jasa dibayar harus berupa angka.',
            'jasa_dibayar.min' => 'Jasa dibayar tidak boleh negatif.',
            'denda_dibayar.required' => 'Denda dibayar wajib diisi.',
            'denda_dibayar.numeric' => 'Denda dibayar harus berupa angka.',
            'denda_dibayar.min' => 'Denda dibayar tidak boleh negatif.',
        ]);

        // Pastikan hanya admin desa yang bisa membuat angsuran
        Gate::authorize('admin_desa');

        // Validasi: Tidak boleh input angsuran ke pinjaman LUNAS
        $pinjaman = Pinjaman::findOrFail($validated['pinjaman_id']);

        // Gunakan status yang dihitung dari sisa pinjaman
        $statusCalculated = $pinjaman->status_pinjaman_calculated;
        if ($statusCalculated === 'lunas') {
            $this->addError('pinjaman_id', 'Tidak dapat menambah angsuran untuk pinjaman yang sudah lunas.');

            return;
        }

        // Validasi: Pokok dibayar tidak boleh melebihi sisa pinjaman
        $sisaPinjaman = $pinjaman->sisa_pinjaman;
        $pokokDibayar = (float) $validated['pokok_dibayar'];

        if ($pokokDibayar > $sisaPinjaman) {
            $this->addError('pokok_dibayar', 'Pokok dibayar tidak boleh melebihi sisa pinjaman (Rp '.number_format($sisaPinjaman, 0, ',', '.').').');

            return;
        }

        // Hitung total dibayar
        $validated['total_dibayar'] = $pokokDibayar + (float) $validated['jasa_dibayar'] + (float) $validated['denda_dibayar'];

        // Convert to proper types
        $validated['pokok_dibayar'] = $pokokDibayar;
        $validated['jasa_dibayar'] = (float) $validated['jasa_dibayar'];
        $validated['denda_dibayar'] = (float) $validated['denda_dibayar'];
        $validated['angsuran_ke'] = (int) $validated['angsuran_ke'];
        $validated['tanggal_bayar'] = Carbon::parse($validated['tanggal_bayar']);

        AngsuranPinjaman::create($validated);

        $this->dispatch('success', message: 'Angsuran berhasil ditambahkan.');
        $this->redirect(route('angsuran.index'), navigate: true);
    }

    public function render()
    {
        $user = Auth::user();

        // Get pinjaman aktif untuk dropdown
        $pinjamanQuery = Pinjaman::aktif()->with('anggota');
        if ($user && $user->desa_id) {
            $pinjamanQuery->where('desa_id', $user->desa_id);
        }
        $pinjaman = $pinjamanQuery->orderBy('tanggal_pinjaman', 'desc')->get();

        return view('livewire.angsuran.create', [
            'pinjaman' => $pinjaman,
        ]);
    }
}
