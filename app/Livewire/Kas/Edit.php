<?php

namespace App\Livewire\Kas;

use App\Models\Akun;
use App\Models\TransaksiKas;
use App\Models\UnitUsaha;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Edit Transaksi Kas'])]
class Edit extends Component
{
    public TransaksiKas $transaksi;

    public $tanggal_transaksi;

    public $unit_usaha_id;

    public $jenis_transaksi;

    public $akun_kas_id;

    public $akun_lawan_id;

    public $jumlah;

    public $uraian;

    public function mount($id): void
    {
        Gate::authorize('admin_desa');

        $this->transaksi = TransaksiKas::with('jurnal')->findOrFail($id);

        // Load data
        $this->tanggal_transaksi = $this->transaksi->tanggal_transaksi->format('Y-m-d');
        $this->unit_usaha_id = $this->transaksi->unit_usaha_id;
        $this->jenis_transaksi = $this->transaksi->jenis_transaksi;
        $this->akun_kas_id = $this->transaksi->akun_kas_id;
        $this->akun_lawan_id = $this->transaksi->akun_lawan_id;
        $this->jumlah = $this->transaksi->jumlah;
        $this->uraian = $this->transaksi->uraian;
    }

    public function update(AccountingService $accountingService): void
    {
        Gate::authorize('admin_desa');

        // Validasi periode tidak boleh closed
        $periode = Carbon::parse($this->tanggal_transaksi)->format('Y-m');
        if ($accountingService->isPeriodClosed($this->transaksi->desa_id, $periode, $this->unit_usaha_id)) {
            throw ValidationException::withMessages([
                'periode' => sprintf(
                    'Periode %s sudah dikunci. Transaksi tidak dapat diubah.',
                    Carbon::createFromFormat('Y-m', $periode)->locale('id')->isoFormat('MMMM YYYY')
                ),
            ]);
        }

        $this->validate([
            'tanggal_transaksi' => 'required|date',
            'jenis_transaksi' => 'required|in:masuk,keluar',
            'akun_kas_id' => 'required|exists:akun,id',
            'akun_lawan_id' => 'required|exists:akun,id',
            'jumlah' => 'required|numeric|min:0.01',
            'uraian' => 'required|string|max:1000',
        ]);

        try {
            DB::transaction(function () use ($accountingService) {
                // Update transaksi kas
                $this->transaksi->update([
                    'unit_usaha_id' => $this->unit_usaha_id,
                    'tanggal_transaksi' => $this->tanggal_transaksi,
                    'uraian' => $this->uraian,
                    'jenis_transaksi' => $this->jenis_transaksi,
                    'akun_kas_id' => $this->akun_kas_id,
                    'akun_lawan_id' => $this->akun_lawan_id,
                    'jumlah' => $this->jumlah,
                ]);

                // Update atau create jurnal
                $details = [];

                if ($this->jenis_transaksi === 'masuk') {
                    $details = [
                        [
                            'akun_id' => $this->akun_kas_id,
                            'posisi' => 'debit',
                            'jumlah' => $this->jumlah,
                            'keterangan' => $this->uraian,
                        ],
                        [
                            'akun_id' => $this->akun_lawan_id,
                            'posisi' => 'kredit',
                            'jumlah' => $this->jumlah,
                            'keterangan' => $this->uraian,
                        ],
                    ];
                } else {
                    $details = [
                        [
                            'akun_id' => $this->akun_lawan_id,
                            'posisi' => 'debit',
                            'jumlah' => $this->jumlah,
                            'keterangan' => $this->uraian,
                        ],
                        [
                            'akun_id' => $this->akun_kas_id,
                            'posisi' => 'kredit',
                            'jumlah' => $this->jumlah,
                            'keterangan' => $this->uraian,
                        ],
                    ];
                }

                if ($this->transaksi->jurnal) {
                    // Update jurnal existing
                    $accountingService->updateJurnal($this->transaksi->jurnal, [
                        'tanggal_transaksi' => $this->tanggal_transaksi,
                        'unit_usaha_id' => $this->unit_usaha_id,
                        'jenis_jurnal' => 'kas_harian',
                        'keterangan' => $this->uraian,
                        'details' => $details,
                    ]);
                } else {
                    // Create jurnal baru jika belum ada
                    $user = Auth::user();
                    $accountingService->createJurnal([
                        'desa_id' => $user->desa_id,
                        'unit_usaha_id' => $this->unit_usaha_id,
                        'tanggal_transaksi' => $this->tanggal_transaksi,
                        'jenis_jurnal' => 'kas_harian',
                        'keterangan' => $this->uraian,
                        'status' => 'posted',
                        'transaksi_kas_id' => $this->transaksi->id,
                        'details' => $details,
                    ]);
                }
            });

            $this->dispatch('success', message: 'Transaksi kas berhasil diupdate.');
            $this->redirect('/kas', navigate: true);

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

        $akunKasList = Akun::aktif()
            ->where('tipe_akun', 'aset')
            ->where(function ($q) {
                $q->where('kode_akun', 'like', '1-10%')
                    ->orWhere('nama_akun', 'like', '%kas%')
                    ->orWhere('nama_akun', 'like', '%bank%');
            })
            ->aktif()
            ->orderBy('kode_akun')
            ->get();

        $akunLawanList = Akun::aktif()
            ->aktif()
            ->orderBy('kode_akun')
            ->get()
            ->groupBy('tipe_akun');

        return view('livewire.kas.edit', [
            'unitUsahaList' => $unitUsahaList,
            'akunKasList' => $akunKasList,
            'akunLawanList' => $akunLawanList,
        ]);
    }
}
