<?php

namespace App\Livewire\Memorial;

use App\Models\Akun;
use App\Models\UnitUsaha;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Tambah Buku Memorial'])]
class Create extends Component
{
    public $tanggal_transaksi;

    public $unit_usaha_id;

    public $keterangan;

    public $status = 'posted';

    // Detail jurnal (debit dan kredit)
    public $details = [];

    public function mount(): void
    {
        Gate::authorize('admin_desa');

        $this->tanggal_transaksi = now()->format('Y-m-d');

        // Inisialisasi dengan 2 baris kosong (minimal)
        $this->details = [
            ['akun_id' => '', 'posisi' => 'debit', 'jumlah' => '', 'keterangan' => ''],
            ['akun_id' => '', 'posisi' => 'kredit', 'jumlah' => '', 'keterangan' => ''],
        ];
    }

    public function addRow(): void
    {
        $this->details[] = ['akun_id' => '', 'posisi' => 'debit', 'jumlah' => '', 'keterangan' => ''];
    }

    public function removeRow($index): void
    {
        if (count($this->details) > 2) {
            unset($this->details[$index]);
            $this->details = array_values($this->details); // Re-index array
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

    public function save(AccountingService $accountingService): void
    {
        Gate::authorize('admin_desa');

        $this->validate([
            'tanggal_transaksi' => 'required|date',
            'keterangan' => 'required|string|max:1000',
            'details' => 'required|array|min:2',
            'details.*.akun_id' => 'required|exists:akun,id',
            'details.*.posisi' => 'required|in:debit,kredit',
            'details.*.jumlah' => 'required|numeric|min:0.01',
        ], [
            'tanggal_transaksi.required' => 'Tanggal transaksi harus diisi',
            'keterangan.required' => 'Keterangan harus diisi',
            'details.required' => 'Detail jurnal harus diisi',
            'details.min' => 'Minimal 2 baris (debit dan kredit)',
            'details.*.akun_id.required' => 'Akun harus dipilih',
            'details.*.akun_id.exists' => 'Akun tidak valid',
            'details.*.posisi.required' => 'Posisi (debit/kredit) harus dipilih',
            'details.*.jumlah.required' => 'Jumlah harus diisi',
            'details.*.jumlah.numeric' => 'Jumlah harus berupa angka',
            'details.*.jumlah.min' => 'Jumlah minimal 0.01',
        ]);

        try {
            $user = Auth::user();

            // Filter details yang kosong
            $validDetails = collect($this->details)
                ->filter(fn ($d) => ! empty($d['akun_id']) && ! empty($d['jumlah']))
                ->values()
                ->toArray();

            $accountingService->createJurnal([
                'desa_id' => $user->desa_id,
                'unit_usaha_id' => $this->unit_usaha_id,
                'tanggal_transaksi' => $this->tanggal_transaksi,
                'jenis_jurnal' => 'memorial',
                'keterangan' => $this->keterangan,
                'status' => $this->status,
                'details' => $validDetails,
            ]);

            $this->dispatch('success', message: 'Jurnal memorial berhasil disimpan.');
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

        return view('livewire.memorial.create', [
            'units' => $units,
            'akunList' => $akunList,
        ]);
    }
}
