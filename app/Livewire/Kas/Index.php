<?php

namespace App\Livewire\Kas;

use App\Models\TransaksiKas;
use App\Models\UnitUsaha;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Kas Harian'])]
class Index extends Component
{
    use WithPagination;

    public $tanggal_dari;

    public $tanggal_sampai;

    public $unit_usaha_id;

    public $jenis_transaksi;

    public ?int $selectedDesaId = null;

    public function mount(): void
    {
        Gate::authorize('view_desa_data');

        $user = Auth::user();

        // Default filter bulan ini
        $this->tanggal_dari = now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_sampai = now()->endOfMonth()->format('Y-m-d');

        // Set default selectedDesaId untuk user yang punya desa_id
        if ($user->desa_id && ! $this->selectedDesaId) {
            $this->selectedDesaId = $user->desa_id;
        }

        // Untuk Super Admin dan Admin Kecamatan, set ke desa pertama yang dapat diakses
        if (! $this->selectedDesaId) {
            $accessibleDesas = $user->getAccessibleDesas();
            if ($accessibleDesas->isNotEmpty()) {
                $this->selectedDesaId = $accessibleDesas->first()->id;
            }
        }
    }

    public function render()
    {
        $user = Auth::user();

        // Get list desa yang dapat diakses
        $accessibleDesas = $user->getAccessibleDesas();

        // Validasi selectedDesaId
        if (! $this->selectedDesaId || ! $user->canAccessDesa($this->selectedDesaId)) {
            return view('livewire.kas.index', [
                'transaksi' => collect([]),
                'units' => collect([]),
                'desas' => $accessibleDesas,
                'error' => 'Silakan pilih desa untuk melihat transaksi kas.',
            ]);
        }

        $query = TransaksiKas::query()
            ->with(['unitUsaha', 'akunKas', 'akunLawan', 'jurnal'])
            ->where('desa_id', $this->selectedDesaId)
            ->whereIn('jenis_transaksi', ['masuk', 'keluar']);

        // Filter tanggal
        if ($this->tanggal_dari) {
            $query->where('tanggal_transaksi', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $query->where('tanggal_transaksi', '<=', $this->tanggal_sampai);
        }

        // Filter unit usaha
        if ($this->unit_usaha_id) {
            $query->where('unit_usaha_id', $this->unit_usaha_id);
        }

        // Filter jenis transaksi
        if ($this->jenis_transaksi) {
            $query->where('jenis_transaksi', $this->jenis_transaksi);
        }

        $transaksi = $query->orderBy('tanggal_transaksi', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        $units = UnitUsaha::where('desa_id', $this->selectedDesaId)
            ->aktif()
            ->orderBy('nama_unit')
            ->get();

        return view('livewire.kas.index', [
            'transaksi' => $transaksi,
            'units' => $units,
            'desas' => $accessibleDesas,
        ]);
    }

    public function delete($id, AccountingService $accountingService): void
    {
        $user = Auth::user();

        // Hanya Super Admin dan Admin Desa yang boleh hapus
        if (! $user->isSuperAdmin() && ! $user->isAdminDesa()) {
            $this->dispatch('error', message: 'Anda tidak memiliki akses untuk menghapus transaksi kas.');

            return;
        }

        $transaksi = TransaksiKas::findOrFail($id);

        // Validasi akses ke desa
        if (! $user->canAccessDesa($transaksi->desa_id)) {
            $this->dispatch('error', message: 'Anda tidak memiliki akses ke desa ini.');

            return;
        }

        // Validasi periode tidak boleh closed
        $periode = Carbon::parse($transaksi->tanggal_transaksi)->format('Y-m');
        if ($accountingService->isPeriodClosed($transaksi->desa_id, $periode, $transaksi->unit_usaha_id)) {
            $this->dispatch('error', message: sprintf(
                'Periode %s sudah dikunci. Transaksi tidak dapat dihapus.',
                Carbon::createFromFormat('Y-m', $periode)->locale('id')->isoFormat('MMMM YYYY')
            ));

            return;
        }

        // Hapus jurnal terkait jika ada
        if ($transaksi->jurnal) {
            $transaksi->jurnal->delete();
        }

        $transaksi->delete();
        $this->dispatch('success', message: 'Transaksi kas berhasil dihapus.');
    }
}
