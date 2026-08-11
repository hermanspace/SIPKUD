<?php

namespace App\Livewire\Kas;

use App\Models\Akun;
use App\Models\TransaksiKas;
use App\Models\UnitUsaha;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Tambah Transaksi Kas'])]
class Create extends Component
{
    public $tanggal_transaksi;

    public $unit_usaha_id;

    public $jenis_transaksi = 'masuk';

    public $akun_kas_id;

    public $akun_lawan_id;

    public $jumlah;

    public $uraian;

    public function mount(): void
    {
        Gate::authorize('admin_desa');

        $this->tanggal_transaksi = now()->format('Y-m-d');
    }

    public function save(AccountingService $accountingService): void
    {
        Gate::authorize('admin_desa');

        $this->validate([
            'tanggal_transaksi' => 'required|date',
            'jenis_transaksi' => 'required|in:masuk,keluar',
            'akun_kas_id' => 'required|exists:akun,id',
            'akun_lawan_id' => 'required|exists:akun,id',
            'jumlah' => 'required|numeric|min:0.01',
            'uraian' => 'required|string|max:1000',
        ], [
            'tanggal_transaksi.required' => 'Tanggal transaksi harus diisi',
            'jenis_transaksi.required' => 'Jenis transaksi harus dipilih',
            'akun_kas_id.required' => 'Akun kas harus dipilih',
            'akun_lawan_id.required' => 'Akun lawan harus dipilih',
            'jumlah.required' => 'Jumlah harus diisi',
            'jumlah.min' => 'Jumlah minimal 0.01',
            'uraian.required' => 'Uraian harus diisi',
        ]);

        try {
            $user = Auth::user();

            DB::transaction(function () use ($user, $accountingService) {
                // Buat transaksi kas
                $transaksiKas = TransaksiKas::create([
                    'desa_id' => $user->desa_id,
                    'unit_usaha_id' => $this->unit_usaha_id,
                    'tanggal_transaksi' => $this->tanggal_transaksi,
                    'uraian' => $this->uraian,
                    'jenis_transaksi' => $this->jenis_transaksi,
                    'akun_kas_id' => $this->akun_kas_id,
                    'akun_lawan_id' => $this->akun_lawan_id,
                    'jumlah' => $this->jumlah,
                ]);

                // Auto-create jurnal
                $details = [];

                if ($this->jenis_transaksi === 'masuk') {
                    // Kas Masuk: Debit Kas, Kredit Akun Lawan (Pendapatan/dll)
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
                    // Kas Keluar: Debit Akun Lawan (Biaya/dll), Kredit Kas
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

                $accountingService->createJurnal([
                    'desa_id' => $user->desa_id,
                    'unit_usaha_id' => $this->unit_usaha_id,
                    'tanggal_transaksi' => $this->tanggal_transaksi,
                    'jenis_jurnal' => 'kas_harian',
                    'keterangan' => $this->uraian,
                    'status' => 'posted',
                    'transaksi_kas_id' => $transaksiKas->id,
                    'details' => $details,
                ]);
            });

            $this->dispatch('success', message: 'Transaksi kas berhasil disimpan dan jurnal telah dibuat.');
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

        // Akun kas/bank
        $akunKasList = Akun::aktif()
            ->where('tipe_akun', 'aset')
            ->where(function ($q) {
                $q->where('kode_akun', 'like', '1-10%') // Kas dan Bank
                    ->orWhere('nama_akun', 'like', '%kas%')
                    ->orWhere('nama_akun', 'like', '%bank%');
            })
            ->orderBy('kode_akun')
            ->get();

        // Akun lawan (semua akun kecuali kas/bank)
        $akunLawanList = Akun::aktif()
            ->orderBy('kode_akun')
            ->get()
            ->groupBy('tipe_akun');

        return view('livewire.kas.create', [
            'unitUsahaList' => $unitUsahaList,
            'akunKasList' => $akunKasList,
            'akunLawanList' => $akunLawanList,
        ]);
    }
}
