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

#[Layout('components.layouts.app', ['title' => 'Saldo Awal Kas'])]
class SaldoAwal extends Component
{
    public $tanggal_saldo_awal;
    public $jumlah_saldo_awal;
    public $keterangan;
    public $akun_kas_id;
    public $akun_lawan_id;
    public $unit_usaha_id;
    
    public $saldoAwalExists = false;
    public $saldoAwalId = null;

    public function mount(): void
    {
        // Hanya Admin Desa yang bisa input saldo awal
        Gate::authorize('admin_desa');
        
        $user = Auth::user();
        
        // Cek apakah sudah ada saldo awal untuk desa ini
        $saldoAwal = TransaksiKas::where('desa_id', $user->desa_id)
                                  ->where('jenis_transaksi', 'saldo_awal')
                                  ->first();
        
        if ($saldoAwal) {
            $this->saldoAwalExists = true;
            $this->saldoAwalId = $saldoAwal->id;
            $this->tanggal_saldo_awal = $saldoAwal->tanggal_transaksi->format('Y-m-d');
            // Tampilkan jumlah; koreksi artefak float (mis. 29999999.99 -> 30000000)
            $raw = $saldoAwal->getRawOriginal('jumlah');
            $nilaiTampil = $raw !== null ? (string) $raw : $this->formatJumlahUntukInput($saldoAwal->jumlah);
            $this->jumlah_saldo_awal = $this->koreksiTampilanJumlah($nilaiTampil);
            $this->keterangan = $saldoAwal->uraian;
            $this->akun_kas_id = $saldoAwal->akun_kas_id;
            $this->akun_lawan_id = $saldoAwal->akun_lawan_id;
            $this->unit_usaha_id = $saldoAwal->unit_usaha_id;
        } else {
            // Default tanggal adalah hari ini
            $this->tanggal_saldo_awal = now()->format('Y-m-d');
            $this->keterangan = 'Saldo awal kas dari sistem manual';
            
            // Default akun kas (ambil akun Kas pertama)
            $akunKas = Akun::aktif()
                ->where('nama_akun', 'Kas')
                ->first();
            if ($akunKas) {
                $this->akun_kas_id = $akunKas->id;
            }
            
            // Default akun lawan (ambil akun Modal pertama)
            $akunModal = Akun::aktif()
                ->where('tipe_akun', 'ekuitas')
                ->where('nama_akun', 'like', '%Modal%')
                ->first();
            if ($akunModal) {
                $this->akun_lawan_id = $akunModal->id;
            }
        }
    }

    public function save(AccountingService $accountingService)
    {
        Gate::authorize('admin_desa');
        
        $user = Auth::user();

        $jumlahResult = $this->normalizeJumlahSaldoAwal($this->jumlah_saldo_awal);
        $this->validate([
            'tanggal_saldo_awal' => 'required|date',
            'jumlah_saldo_awal' => 'required|string',
            'keterangan' => 'required|string|max:255',
            'akun_kas_id' => 'required|exists:akun,id',
            'akun_lawan_id' => 'required|exists:akun,id',
        ], [
            'tanggal_saldo_awal.required' => 'Tanggal saldo awal harus diisi',
            'tanggal_saldo_awal.date' => 'Format tanggal tidak valid',
            'jumlah_saldo_awal.required' => 'Jumlah saldo awal harus diisi',
            'jumlah_saldo_awal.string' => 'Jumlah saldo awal tidak valid',
            'keterangan.required' => 'Keterangan harus diisi',
            'keterangan.max' => 'Keterangan maksimal 255 karakter',
            'akun_kas_id.required' => 'Akun kas harus dipilih',
            'akun_kas_id.exists' => 'Akun kas tidak valid',
            'akun_lawan_id.required' => 'Akun lawan harus dipilih',
            'akun_lawan_id.exists' => 'Akun lawan tidak valid',
        ]);

        if ($jumlahResult === null || $jumlahResult['valid'] === false) {
            throw ValidationException::withMessages([
                'jumlah_saldo_awal' => ['Jumlah saldo awal harus berupa angka yang valid.'],
            ]);
        }

        $jumlahUntukSimpan = $jumlahResult['value'];

        try {
            DB::transaction(function () use ($accountingService, $user, $jumlahUntukSimpan) {
                if ($this->saldoAwalExists) {
                    // Update saldo awal yang sudah ada
                    $saldoAwal = TransaksiKas::find($this->saldoAwalId);
                    
                    // Update transaksi kas
                    $saldoAwal->update([
                        'tanggal_transaksi' => $this->tanggal_saldo_awal,
                        'jumlah' => $jumlahUntukSimpan,
                        'uraian' => $this->keterangan,
                        'akun_kas_id' => $this->akun_kas_id,
                        'akun_lawan_id' => $this->akun_lawan_id,
                        'unit_usaha_id' => $this->unit_usaha_id,
                    ]);
                    
                    // Update jurnal jika ada (pakai method khusus saldo awal: boleh posted & abaikan periode closed)
                    if ($saldoAwal->jurnal) {
                        $accountingService->updateJurnalForSaldoAwal($saldoAwal->jurnal, [
                            'tanggal_transaksi' => $this->tanggal_saldo_awal,
                            'unit_usaha_id' => $this->unit_usaha_id,
                            'jenis_jurnal' => 'kas_harian',
                            'keterangan' => $this->keterangan,
                            'details' => [
                                [
                                    'akun_id' => $this->akun_kas_id,
                                    'posisi' => 'debit',
                                    'jumlah' => $jumlahUntukSimpan,
                                    'keterangan' => 'Saldo awal kas',
                                ],
                                [
                                    'akun_id' => $this->akun_lawan_id,
                                    'posisi' => 'kredit',
                                    'jumlah' => $jumlahUntukSimpan,
                                    'keterangan' => 'Saldo awal kas',
                                ],
                            ],
                        ]);
                    } else {
                        // Create jurnal baru jika belum ada
                        $accountingService->createJurnal([
                            'desa_id' => $user->desa_id,
                            'unit_usaha_id' => $this->unit_usaha_id,
                            'tanggal_transaksi' => $this->tanggal_saldo_awal,
                            'jenis_jurnal' => 'kas_harian',
                            'keterangan' => $this->keterangan,
                            'status' => 'posted',
                            'transaksi_kas_id' => $saldoAwal->id,
                            'details' => [
                                [
                                    'akun_id' => $this->akun_kas_id,
                                    'posisi' => 'debit',
                                    'jumlah' => $jumlahUntukSimpan,
                                    'keterangan' => 'Saldo awal kas',
                                ],
                                [
                                    'akun_id' => $this->akun_lawan_id,
                                    'posisi' => 'kredit',
                                    'jumlah' => $jumlahUntukSimpan,
                                    'keterangan' => 'Saldo awal kas',
                                ],
                            ],
                        ], allowClosedPeriod: true);
                    }

                    $this->dispatch('success', message: 'Saldo awal berhasil diupdate');
                } else {
                    // Buat saldo awal baru
                    $transaksiKas = TransaksiKas::create([
                        'desa_id' => $user->desa_id,
                        'unit_usaha_id' => $this->unit_usaha_id,
                        'tanggal_transaksi' => $this->tanggal_saldo_awal,
                        'uraian' => $this->keterangan,
                        'jenis_transaksi' => 'saldo_awal',
                        'akun_kas_id' => $this->akun_kas_id,
                        'akun_lawan_id' => $this->akun_lawan_id,
                        'jumlah' => $jumlahUntukSimpan,
                    ]);
                    
                    // Auto-create jurnal
                    $accountingService->createJurnal([
                        'desa_id' => $user->desa_id,
                        'unit_usaha_id' => $this->unit_usaha_id,
                        'tanggal_transaksi' => $this->tanggal_saldo_awal,
                        'jenis_jurnal' => 'kas_harian',
                        'keterangan' => $this->keterangan,
                        'status' => 'posted',
                        'transaksi_kas_id' => $transaksiKas->id,
                        'details' => [
                            [
                                'akun_id' => $this->akun_kas_id,
                                'posisi' => 'debit',
                                'jumlah' => $jumlahUntukSimpan,
                                'keterangan' => 'Saldo awal kas',
                            ],
                            [
                                'akun_id' => $this->akun_lawan_id,
                                'posisi' => 'kredit',
                                'jumlah' => $jumlahUntukSimpan,
                                'keterangan' => 'Saldo awal kas',
                            ],
                        ],
                    ], allowClosedPeriod: true);

                    $this->dispatch('success', message: 'Saldo awal berhasil disimpan dan jurnal telah dibuat.');
                    $this->saldoAwalExists = true;
                    $this->saldoAwalId = $transaksiKas->id;
                }
            });
        } catch (ValidationException $e) {
            $this->dispatch('error', message: $e->getMessage());
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Normalisasi input jumlah saldo awal (string) menjadi nilai decimal yang aman.
     * Menghindari galat presisi float dengan parsing manual; nilai disimpan sebagai string.
     * Menerima format: 30000000, 30.000.000, 30000000,5
     *
     * @return array{value: string, valid: bool}|null ['value' => '30000000.00', 'valid' => true] atau null
     */
    protected function normalizeJumlahSaldoAwal($input): ?array
    {
        if ($input === null || $input === '') {
            return null;
        }
        $s = preg_replace('/\s+/', '', (string) $input);
        $negatif = str_starts_with($s, '-');
        if ($negatif) {
            $s = substr($s, 1);
        }
        $s = str_replace(',', '.', $s);
        $s = preg_replace('/[^0-9.]/', '', $s);
        if ($s === '') {
            return null;
        }
        $parts = explode('.', $s);
        if (count($parts) > 2) {
            $intPart = implode('', array_slice($parts, 0, -1));
            $decPart = end($parts);
        } elseif (count($parts) === 2) {
            $intPart = $parts[0];
            $decPart = $parts[1];
        } else {
            $intPart = $parts[0];
            $decPart = '0';
        }
        $decPart = substr($decPart . '00', 0, 2);
        $intPart = ltrim($intPart, '0') ?: '0';
        if (! ctype_digit($intPart) || strlen($intPart) > 15) {
            return null;
        }
        $value = ($negatif ? '-' : '') . $intPart . '.' . $decPart;
        return ['value' => $value, 'valid' => true];
    }

    /**
     * Format nilai dari DB untuk ditampilkan di input (tanpa presisi float).
     */
    protected function formatJumlahUntukInput($jumlah): string
    {
        $raw = is_object($jumlah) ? (string) $jumlah : $jumlah;
        if ($raw === '' || $raw === null) {
            return '0';
        }
        $n = (float) $raw;
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }

    /**
     * Koreksi tampilan jumlah: nilai yang diduga artefak float (mis. 29999999.99)
     * ditampilkan sebagai bilangan bulat yang benar (30000000).
     */
    protected function koreksiTampilanJumlah(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value === '-') {
            return '0';
        }
        $negatif = str_starts_with($value, '-');
        if ($negatif) {
            $value = substr($value, 1);
        }
        $value = str_replace(',', '.', $value);
        if (! preg_match('/^(\d+)\.?(\d*)$/', $value, $m)) {
            return $negatif ? '-' . $value : $value;
        }
        $intPart = $m[1];
        $decPart = isset($m[2]) ? substr($m[2] . '00', 0, 2) : '00';
        $prefix = $negatif ? '-' : '';
        // Artefak float: .99 atau .01 pada bilangan besar -> bulatkan ke integer terdekat
        if (strlen($intPart) >= 2 && in_array($decPart, ['99', '01'], true)) {
            $int = (int) $intPart;
            $bulat = $decPart === '99' ? $int + 1 : $int;
            return $prefix . (string) $bulat;
        }
        if ($decPart === '00' || $decPart === '0') {
            return $prefix . ltrim($intPart, '0') ?: '0';
        }
        return $prefix . ltrim($intPart, '0') . '.' . $decPart;
    }

    public function render()
    {
        $user = Auth::user();
        
        $akunKas = Akun::aktif()
            ->where('tipe_akun', 'aset')
            ->whereIn('nama_akun', ['Kas', 'Bank', 'Kas Kecil'])
            ->orderBy('kode_akun')
            ->get();
        
        $akunLawan = Akun::aktif()
            ->where('tipe_akun', 'ekuitas')
            ->orderBy('kode_akun')
            ->get();
        
        $unitUsaha = UnitUsaha::where('desa_id', $user->desa_id)
            ->aktif()
            ->orderBy('nama_unit')
            ->get();
        
        return view('livewire.kas.saldo-awal', [
            'akunKas' => $akunKas,
            'akunLawan' => $akunLawan,
            'unitUsaha' => $unitUsaha,
        ]);
    }
}
