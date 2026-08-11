<?php

namespace App\Livewire\Memorial;

use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\UnitUsaha;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Edit Buku Memorial'])]
class Edit extends Component
{
    public Jurnal $jurnal;

    public $tanggal_transaksi;

    public $unit_usaha_id;

    public $keterangan;

    public $details = [];

    public function mount($id): void
    {
        Gate::authorize('admin_desa');

        $this->jurnal = Jurnal::with('details.akun')->findOrFail($id);

        // Validasi: hanya draft yang bisa diedit
        if ($this->jurnal->status !== 'draft') {
            abort(403, 'Hanya jurnal draft yang dapat diedit.');
        }

        // Validasi periode tidak boleh closed
        $periode = Carbon::parse($this->jurnal->tanggal_transaksi)->format('Y-m');
        $accountingService = app(AccountingService::class);
        if ($accountingService->isPeriodClosed($this->jurnal->desa_id, $periode, $this->jurnal->unit_usaha_id)) {
            abort(403, sprintf(
                'Periode %s sudah dikunci. Transaksi tidak dapat diubah.',
                Carbon::createFromFormat('Y-m', $periode)->locale('id')->isoFormat('MMMM YYYY')
            ));
        }

        // Load data
        $this->tanggal_transaksi = $this->jurnal->tanggal_transaksi->format('Y-m-d');
        $this->unit_usaha_id = $this->jurnal->unit_usaha_id;
        $this->keterangan = $this->jurnal->keterangan;

        // Load details
        $this->details = $this->jurnal->details->map(function ($detail) {
            return [
                'akun_id' => $detail->akun_id,
                'posisi' => $detail->posisi,
                'jumlah' => $detail->jumlah,
                'keterangan' => $detail->keterangan,
            ];
        })->toArray();
    }

    public function addRow(): void
    {
        $this->details[] = ['akun_id' => '', 'posisi' => 'debit', 'jumlah' => '', 'keterangan' => ''];
    }

    public function removeRow($index): void
    {
        if (count($this->details) > 2) {
            unset($this->details[$index]);
            $this->details = array_values($this->details);
        }
    }

    public function getTotalDebitProperty()
    {
        return collect($this->details)
            ->filter(fn ($d) => ($d['posisi'] ?? '') === 'debit')
            ->sum(fn ($d) => floatval($d['jumlah'] ?? 0));
    }

    public function getTotalKreditProperty()
    {
        return collect($this->details)
            ->filter(fn ($d) => ($d['posisi'] ?? '') === 'kredit')
            ->sum(fn ($d) => floatval($d['jumlah'] ?? 0));
    }

    public function update(AccountingService $accountingService): void
    {
        Gate::authorize('admin_desa');

        // Validasi periode tidak boleh closed
        $periode = Carbon::parse($this->tanggal_transaksi)->format('Y-m');
        if ($accountingService->isPeriodClosed($this->jurnal->desa_id, $periode, $this->unit_usaha_id)) {
            throw ValidationException::withMessages([
                'periode' => sprintf(
                    'Periode %s sudah dikunci. Transaksi tidak dapat diubah.',
                    Carbon::createFromFormat('Y-m', $periode)->locale('id')->isoFormat('MMMM YYYY')
                ),
            ]);
        }

        $this->validate([
            'tanggal_transaksi' => 'required|date',
            'keterangan' => 'required|string|max:1000',
            'details' => 'required|array|min:2',
            'details.*.akun_id' => 'required|exists:akun,id',
            'details.*.posisi' => 'required|in:debit,kredit',
            'details.*.jumlah' => 'required|numeric|min:0.01',
        ]);

        try {
            $validDetails = collect($this->details)
                ->filter(fn ($d) => ! empty($d['akun_id']) && ! empty($d['jumlah']))
                ->values()
                ->toArray();

            $accountingService->updateJurnal($this->jurnal, [
                'tanggal_transaksi' => $this->tanggal_transaksi,
                'unit_usaha_id' => $this->unit_usaha_id,
                'jenis_jurnal' => 'memorial',
                'keterangan' => $this->keterangan,
                'details' => $validDetails,
            ]);

            $this->dispatch('success', message: 'Jurnal memorial berhasil diupdate.');
            $this->redirect('/memorial', navigate: true);

        } catch (ValidationException $e) {
            $this->dispatch('error', message: $e->getMessage());
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function render()
    {
        $user = Auth::user();

        $unitUsahaList = UnitUsaha::where('desa_id', $user->desa_id)
            ->aktif()
            ->orderBy('nama_unit')
            ->get();

        $akunList = Akun::aktif()
            ->aktif()
            ->orderBy('kode_akun')
            ->get();

        $units = $unitUsahaList;

        return view('livewire.memorial.edit', [
            'units' => $units,
            'akunList' => $akunList,
        ]);
    }
}
