<?php

namespace App\Services;

use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\NeracaSaldo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * AccountingService
 * 
 * Service untuk menangani operasi akuntansi double entry
 * Prinsip utama: Debit = Kredit (always balanced)
 * 
 * PENTING: Semua transaksi harus melalui service ini
 * untuk memastikan integritas data akuntansi
 */
class AccountingService
{
    /**
     * Buat jurnal baru dengan validasi double entry
     * 
     * @param array $data
     * @return Jurnal
     * @throws ValidationException
     */
    public function createJurnal(array $data): Jurnal
    {
        // Validasi data
        $this->validateJurnalData($data);
        
        // Validasi balance (debit = kredit)
        $this->validateBalance($data['details']);
        
        return DB::transaction(function () use ($data) {
            // Hitung total debit dan kredit
            $totals = $this->calculateTotals($data['details']);
            
            // Buat header jurnal
            $jurnal = Jurnal::create([
                'desa_id' => $data['desa_id'],
                'unit_usaha_id' => $data['unit_usaha_id'] ?? null,
                'tanggal_transaksi' => $data['tanggal_transaksi'],
                'jenis_jurnal' => $data['jenis_jurnal'],
                'keterangan' => $data['keterangan'],
                'total_debit' => $totals['debit'],
                'total_kredit' => $totals['kredit'],
                'status' => $data['status'] ?? 'posted',
                'transaksi_kas_id' => $data['transaksi_kas_id'] ?? null,
                'pinjaman_id' => $data['pinjaman_id'] ?? null,
                'angsuran_pinjaman_id' => $data['angsuran_pinjaman_id'] ?? null,
                'created_by' => Auth::id(),
            ]);
            
            // Buat detail jurnal
            foreach ($data['details'] as $detail) {
                JurnalDetail::create([
                    'jurnal_id' => $jurnal->id,
                    'akun_id' => $detail['akun_id'],
                    'posisi' => $detail['posisi'],
                    'jumlah' => $detail['jumlah'],
                    'keterangan' => $detail['keterangan'] ?? null,
                ]);
            }
            
            // Auto-post ke ledger jika status = 'posted'
            if ($jurnal->status === 'posted') {
                $this->postToLedger($jurnal);
            }
            
            return $jurnal->load('details.akun');
        });
    }

    /**
     * Update jurnal existing
     * 
     * @param Jurnal $jurnal
     * @param array $data
     * @return Jurnal
     * @throws ValidationException
     */
    public function updateJurnal(Jurnal $jurnal, array $data): Jurnal
    {
        // Validasi status (hanya draft yang bisa diupdate)
        if ($jurnal->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Hanya jurnal dengan status draft yang dapat diubah.',
            ]);
        }
        
        // Validasi periode tidak boleh closed
        $periode = Carbon::parse($jurnal->tanggal_transaksi)->format('Y-m');
        if ($this->isPeriodClosed($jurnal->desa_id, $periode, $jurnal->unit_usaha_id)) {
            throw ValidationException::withMessages([
                'periode' => sprintf(
                    'Periode %s sudah dikunci. Transaksi tidak dapat diubah.',
                    Carbon::createFromFormat('Y-m', $periode)->locale('id')->isoFormat('MMMM YYYY')
                ),
            ]);
        }
        
        // Validasi data
        $this->validateJurnalData($data);
        
        // Validasi balance
        $this->validateBalance($data['details']);
        
        return DB::transaction(function () use ($jurnal, $data) {
            // Hapus detail lama
            $jurnal->details()->delete();
            
            // Hitung total baru
            $totals = $this->calculateTotals($data['details']);
            
            // Update header
            $jurnal->update([
                'unit_usaha_id' => $data['unit_usaha_id'] ?? $jurnal->unit_usaha_id,
                'tanggal_transaksi' => $data['tanggal_transaksi'],
                'jenis_jurnal' => $data['jenis_jurnal'],
                'keterangan' => $data['keterangan'],
                'total_debit' => $totals['debit'],
                'total_kredit' => $totals['kredit'],
                'updated_by' => Auth::id(),
            ]);
            
            // Buat detail baru
            foreach ($data['details'] as $detail) {
                JurnalDetail::create([
                    'jurnal_id' => $jurnal->id,
                    'akun_id' => $detail['akun_id'],
                    'posisi' => $detail['posisi'],
                    'jumlah' => $detail['jumlah'],
                    'keterangan' => $detail['keterangan'] ?? null,
                ]);
            }
            
            return $jurnal->fresh(['details.akun']);
        });
    }

    /**
     * Update jurnal saldo awal (khusus).
     * Mengizinkan update jurnal posted dan mengabaikan periode closed,
     * lalu recalculate neraca_saldo untuk periode tersebut.
     *
     * @param Jurnal $jurnal
     * @param array $data
     * @return Jurnal
     */
    public function updateJurnalForSaldoAwal(Jurnal $jurnal, array $data): Jurnal
    {
        $this->validateJurnalData($data);
        $this->validateBalance($data['details']);

        $periode = Carbon::parse($data['tanggal_transaksi'])->format('Y-m');

        return DB::transaction(function () use ($jurnal, $data, $periode) {
            $jurnal->details()->delete();
            $totals = $this->calculateTotals($data['details']);

            $jurnal->update([
                'unit_usaha_id' => $data['unit_usaha_id'] ?? $jurnal->unit_usaha_id,
                'tanggal_transaksi' => $data['tanggal_transaksi'],
                'jenis_jurnal' => $data['jenis_jurnal'],
                'keterangan' => $data['keterangan'],
                'total_debit' => $totals['debit'],
                'total_kredit' => $totals['kredit'],
                'updated_by' => Auth::id(),
            ]);

            foreach ($data['details'] as $detail) {
                JurnalDetail::create([
                    'jurnal_id' => $jurnal->id,
                    'akun_id' => $detail['akun_id'],
                    'posisi' => $detail['posisi'],
                    'jumlah' => $detail['jumlah'],
                    'keterangan' => $detail['keterangan'] ?? null,
                ]);
            }

            $this->recalculateBalance($jurnal->desa_id, $periode, $jurnal->unit_usaha_id);

            return $jurnal->fresh(['details.akun']);
        });
    }

    /**
     * Void jurnal (pembatalan)
     * 
     * @param Jurnal $jurnal
     * @return Jurnal
     */
    public function voidJurnal(Jurnal $jurnal): Jurnal
    {
        // Validasi periode tidak boleh closed
        $periode = Carbon::parse($jurnal->tanggal_transaksi)->format('Y-m');
        if ($this->isPeriodClosed($jurnal->desa_id, $periode, $jurnal->unit_usaha_id)) {
            throw ValidationException::withMessages([
                'periode' => sprintf(
                    'Periode %s sudah dikunci. Transaksi tidak dapat dibatalkan.',
                    Carbon::createFromFormat('Y-m', $periode)->locale('id')->isoFormat('MMMM YYYY')
                ),
            ]);
        }
        
        $jurnal->update([
            'status' => 'void',
            'updated_by' => Auth::id(),
        ]);
        
        return $jurnal;
    }

    /**
     * Post jurnal (dari draft ke posted)
     * 
     * @param Jurnal $jurnal
     * @return Jurnal
     */
    public function postJurnal(Jurnal $jurnal): Jurnal
    {
        if (!$jurnal->isBalanced()) {
            throw ValidationException::withMessages([
                'balance' => 'Jurnal tidak balance (debit ≠ kredit).',
            ]);
        }
        
        $jurnal->update([
            'status' => 'posted',
            'updated_by' => Auth::id(),
        ]);
        
        // Auto-post ke ledger setelah status menjadi 'posted'
        $this->postToLedger($jurnal);
        
        return $jurnal;
    }

    /**
     * Get Neraca Saldo dari tabel neraca_saldo (ledger)
     * Format lengkap: Saldo Awal, Mutasi, Saldo Akhir
     * Semua akun tampil (termasuk yang tanpa transaksi)
     * 
     * @param int $desaId
     * @param string $periode Format: YYYY-MM (contoh: 2026-01)
     * @param int|null $unitUsahaId
     * @return array
     */
    public function getNeracaSaldoFromLedger(int $desaId, string $periode, ?int $unitUsahaId = null): array
    {
        // Query semua akun dengan LEFT JOIN ke neraca_saldo
        // Semua akun akan tampil, termasuk yang tanpa transaksi
        // Gunakan withoutGlobalScopes() untuk menghindari konflik dengan HasDesaScope
        
        if ($unitUsahaId !== null) {
            // Filter by unit usaha tertentu
            $query = Akun::withoutGlobalScopes()
                ->leftJoin('neraca_saldo', function ($join) use ($desaId, $periode, $unitUsahaId) {
                    $join->on('neraca_saldo.akun_id', '=', 'akun.id')
                         ->where('neraca_saldo.desa_id', '=', $desaId)
                         ->where('neraca_saldo.periode', '=', $periode)
                         ->where('neraca_saldo.unit_usaha_id', '=', $unitUsahaId);
                })
                ->where('akun.status', 'aktif')
                ->whereNull('akun.deleted_at')
                ->select(
                    'akun.id as akun_id',
                    'akun.kode_akun',
                    'akun.nama_akun',
                    'akun.tipe_akun',
                    DB::raw('COALESCE(neraca_saldo.saldo_awal_debit, 0) as saldo_awal_debit'),
                    DB::raw('COALESCE(neraca_saldo.saldo_awal_kredit, 0) as saldo_awal_kredit'),
                    DB::raw('COALESCE(neraca_saldo.mutasi_debit, 0) as mutasi_debit'),
                    DB::raw('COALESCE(neraca_saldo.mutasi_kredit, 0) as mutasi_kredit'),
                    DB::raw('COALESCE(neraca_saldo.saldo_akhir_debit, 0) as saldo_akhir_debit'),
                    DB::raw('COALESCE(neraca_saldo.saldo_akhir_kredit, 0) as saldo_akhir_kredit')
                )
                ->orderBy('akun.kode_akun');
            
            $results = $query->get();
        } else {
            // Aggregate semua unit usaha (termasuk yang null)
            $query = Akun::withoutGlobalScopes()
                ->leftJoin('neraca_saldo', function ($join) use ($desaId, $periode) {
                    $join->on('neraca_saldo.akun_id', '=', 'akun.id')
                         ->where('neraca_saldo.desa_id', '=', $desaId)
                         ->where('neraca_saldo.periode', '=', $periode);
                })
                ->where('akun.status', 'aktif')
                ->whereNull('akun.deleted_at')
                ->select(
                    'akun.id as akun_id',
                    'akun.kode_akun',
                    'akun.nama_akun',
                    'akun.tipe_akun',
                    DB::raw('COALESCE(SUM(neraca_saldo.saldo_awal_debit), 0) as saldo_awal_debit'),
                    DB::raw('COALESCE(SUM(neraca_saldo.saldo_awal_kredit), 0) as saldo_awal_kredit'),
                    DB::raw('COALESCE(SUM(neraca_saldo.mutasi_debit), 0) as mutasi_debit'),
                    DB::raw('COALESCE(SUM(neraca_saldo.mutasi_kredit), 0) as mutasi_kredit'),
                    DB::raw('COALESCE(SUM(neraca_saldo.saldo_akhir_debit), 0) as saldo_akhir_debit'),
                    DB::raw('COALESCE(SUM(neraca_saldo.saldo_akhir_kredit), 0) as saldo_akhir_kredit')
                )
                ->groupBy('akun.id', 'akun.kode_akun', 'akun.nama_akun', 'akun.tipe_akun')
                ->orderBy('akun.kode_akun');
            
            $results = $query->get();
        }
        
        return $results->map(function ($item) {
            return [
                'akun_id' => (int) $item->akun_id,
                'kode_akun' => $item->kode_akun,
                'nama_akun' => $item->nama_akun,
                'tipe_akun' => $item->tipe_akun,
                'saldo_awal_debit' => (float) $item->saldo_awal_debit,
                'saldo_awal_kredit' => (float) $item->saldo_awal_kredit,
                'mutasi_debit' => (float) $item->mutasi_debit,
                'mutasi_kredit' => (float) $item->mutasi_kredit,
                'saldo_akhir_debit' => (float) $item->saldo_akhir_debit,
                'saldo_akhir_kredit' => (float) $item->saldo_akhir_kredit,
            ];
        })->toArray();
    }

    /**
     * Hitung neraca saldo untuk periode tertentu (LEGACY - dari jurnal)
     * 
     * @deprecated Gunakan getNeracaSaldoFromLedger() untuk format lengkap
     * @param int $desaId
     * @param int|null $bulan
     * @param int|null $tahun
     * @param int|null $unitUsahaId
     * @return array
     */
    public function getNeracaSaldo(int $desaId, ?int $bulan = null, ?int $tahun = null, ?int $unitUsahaId = null): array
    {
        $query = JurnalDetail::query()
            ->join('jurnal', 'jurnal_detail.jurnal_id', '=', 'jurnal.id')
            ->join('akun', 'jurnal_detail.akun_id', '=', 'akun.id')
            ->where('jurnal.desa_id', $desaId)
            ->where('jurnal.status', 'posted');
        
        // Filter unit usaha
        if ($unitUsahaId) {
            $query->where('jurnal.unit_usaha_id', $unitUsahaId);
        }
        
        // Filter periode
        if ($bulan && $tahun) {
            $query->whereMonth('jurnal.tanggal_transaksi', $bulan)
                  ->whereYear('jurnal.tanggal_transaksi', $tahun);
        } elseif ($tahun) {
            $query->whereYear('jurnal.tanggal_transaksi', $tahun);
        }
        
        // Group by akun dan hitung total debit/kredit
        $results = $query->select(
            'akun.id',
            'akun.kode_akun',
            'akun.nama_akun',
            'akun.tipe_akun',
            DB::raw("SUM(CASE WHEN jurnal_detail.posisi = 'debit' THEN jurnal_detail.jumlah ELSE 0 END) as total_debit"),
            DB::raw("SUM(CASE WHEN jurnal_detail.posisi = 'kredit' THEN jurnal_detail.jumlah ELSE 0 END) as total_kredit")
        )
        ->groupBy('akun.id', 'akun.kode_akun', 'akun.nama_akun', 'akun.tipe_akun')
        ->orderBy('akun.kode_akun')
        ->get();
        
        // Hitung saldo per akun berdasarkan normal balance
        $neracaSaldo = $results->map(function ($item) {
            // Normal balance: Aset & Beban = Debit, Kewajiban & Ekuitas & Pendapatan = Kredit
            $normalDebit = in_array($item->tipe_akun, ['aset', 'beban']);
            
            if ($normalDebit) {
                $saldo = $item->total_debit - $item->total_kredit;
                $posisiSaldo = $saldo >= 0 ? 'debit' : 'kredit';
            } else {
                $saldo = $item->total_kredit - $item->total_debit;
                $posisiSaldo = $saldo >= 0 ? 'kredit' : 'debit';
            }
            
            return [
                'akun_id' => $item->id,
                'kode_akun' => $item->kode_akun,
                'nama_akun' => $item->nama_akun,
                'tipe_akun' => $item->tipe_akun,
                'total_debit' => (float) $item->total_debit,
                'total_kredit' => (float) $item->total_kredit,
                'saldo' => abs($saldo),
                'posisi_saldo' => $posisiSaldo,
            ];
        });
        
        return $neracaSaldo->toArray();
    }

    /**
     * Get Laba Rugi dari tabel neraca_saldo (ledger)
     * Support 2 mode: Bulanan (mutasi) dan Kumulatif (saldo akhir)
     * 
     * @param int $desaId
     * @param string $periode Format: YYYY-MM (contoh: 2026-01)
     * @param string $mode 'bulanan' atau 'kumulatif'
     * @param int|null $unitUsahaId
     * @return array
     */
    public function getLabaRugiFromLedger(
        int $desaId, 
        string $periode, 
        string $mode = 'bulanan',
        ?int $unitUsahaId = null
    ): array {
        // Query akun pendapatan dan beban dari neraca_saldo
        if ($unitUsahaId !== null) {
            // Filter by unit usaha tertentu
            $query = Akun::withoutGlobalScopes()
                ->leftJoin('neraca_saldo', function ($join) use ($desaId, $periode, $unitUsahaId) {
                    $join->on('neraca_saldo.akun_id', '=', 'akun.id')
                         ->where('neraca_saldo.desa_id', '=', $desaId)
                         ->where('neraca_saldo.periode', '=', $periode)
                         ->where('neraca_saldo.unit_usaha_id', '=', $unitUsahaId);
                })
                ->where('akun.status', 'aktif')
                ->whereIn('akun.tipe_akun', ['pendapatan', 'beban'])
                ->whereNull('akun.deleted_at')
                ->select(
                    'akun.id as akun_id',
                    'akun.kode_akun',
                    'akun.nama_akun',
                    'akun.tipe_akun',
                    DB::raw('COALESCE(neraca_saldo.mutasi_debit, 0) as mutasi_debit'),
                    DB::raw('COALESCE(neraca_saldo.mutasi_kredit, 0) as mutasi_kredit'),
                    DB::raw('COALESCE(neraca_saldo.saldo_akhir_debit, 0) as saldo_akhir_debit'),
                    DB::raw('COALESCE(neraca_saldo.saldo_akhir_kredit, 0) as saldo_akhir_kredit')
                )
                ->orderBy('akun.kode_akun');
            
            $results = $query->get();
        } else {
            // Aggregate semua unit usaha
            $query = Akun::withoutGlobalScopes()
                ->leftJoin('neraca_saldo', function ($join) use ($desaId, $periode) {
                    $join->on('neraca_saldo.akun_id', '=', 'akun.id')
                         ->where('neraca_saldo.desa_id', '=', $desaId)
                         ->where('neraca_saldo.periode', '=', $periode);
                })
                ->where('akun.status', 'aktif')
                ->whereIn('akun.tipe_akun', ['pendapatan', 'beban'])
                ->whereNull('akun.deleted_at')
                ->select(
                    'akun.id as akun_id',
                    'akun.kode_akun',
                    'akun.nama_akun',
                    'akun.tipe_akun',
                    DB::raw('COALESCE(SUM(neraca_saldo.mutasi_debit), 0) as mutasi_debit'),
                    DB::raw('COALESCE(SUM(neraca_saldo.mutasi_kredit), 0) as mutasi_kredit'),
                    DB::raw('COALESCE(SUM(neraca_saldo.saldo_akhir_debit), 0) as saldo_akhir_debit'),
                    DB::raw('COALESCE(SUM(neraca_saldo.saldo_akhir_kredit), 0) as saldo_akhir_kredit')
                )
                ->groupBy('akun.id', 'akun.kode_akun', 'akun.nama_akun', 'akun.tipe_akun')
                ->orderBy('akun.kode_akun');
            
            $results = $query->get();
        }
        
        // Pisahkan pendapatan dan beban
        $pendapatanList = [];
        $bebanList = [];
        
        foreach ($results as $item) {
            if ($mode === 'bulanan') {
                // Mode Bulanan: gunakan mutasi
                // Pendapatan: normal kredit, jadi jumlah = mutasi_kredit
                // Beban: normal debit, jadi jumlah = mutasi_debit
                $jumlah = $item->tipe_akun === 'pendapatan' 
                    ? (float) $item->mutasi_kredit 
                    : (float) $item->mutasi_debit;
                
                $data = [
                    'akun_id' => (int) $item->akun_id,
                    'kode_akun' => $item->kode_akun,
                    'nama_akun' => $item->nama_akun,
                    'mutasi_debit' => (float) $item->mutasi_debit,
                    'mutasi_kredit' => (float) $item->mutasi_kredit,
                    'jumlah' => $jumlah,
                ];
            } else {
                // Mode Kumulatif: gunakan saldo akhir
                // Pendapatan: normal kredit, jadi jumlah = saldo_akhir_kredit
                // Beban: normal debit, jadi jumlah = saldo_akhir_debit
                $jumlah = $item->tipe_akun === 'pendapatan' 
                    ? (float) $item->saldo_akhir_kredit 
                    : (float) $item->saldo_akhir_debit;
                
                $data = [
                    'akun_id' => (int) $item->akun_id,
                    'kode_akun' => $item->kode_akun,
                    'nama_akun' => $item->nama_akun,
                    'saldo_akhir_debit' => (float) $item->saldo_akhir_debit,
                    'saldo_akhir_kredit' => (float) $item->saldo_akhir_kredit,
                    'jumlah' => $jumlah,
                ];
            }
            
            if ($item->tipe_akun === 'pendapatan') {
                $pendapatanList[] = $data;
            } else {
                $bebanList[] = $data;
            }
        }
        
        // Hitung total
        $totalPendapatan = collect($pendapatanList)->sum('jumlah');
        $totalBeban = collect($bebanList)->sum('jumlah');
        $labaBersih = $totalPendapatan - $totalBeban;
        
        return [
            'mode' => $mode,
            'periode' => $periode,
            'pendapatan' => $totalPendapatan,
            'beban' => $totalBeban,
            'laba_bersih' => $labaBersih,
            'detail_pendapatan' => $pendapatanList,
            'detail_beban' => $bebanList,
        ];
    }

    /**
     * Hitung laba rugi untuk periode tertentu (LEGACY - dari jurnal)
     * 
     * @deprecated Gunakan getLabaRugiFromLedger() untuk format lengkap
     * @param int $desaId
     * @param int $bulan
     * @param int $tahun
     * @param int|null $unitUsahaId
     * @return array
     */
    public function getLabaRugi(int $desaId, int $bulan, int $tahun, ?int $unitUsahaId = null): array
    {
        $neracaSaldo = $this->getNeracaSaldo($desaId, $bulan, $tahun, $unitUsahaId);
        
        $pendapatan = collect($neracaSaldo)
            ->where('tipe_akun', 'pendapatan')
            ->sum('saldo');
        
        $beban = collect($neracaSaldo)
            ->where('tipe_akun', 'beban')
            ->sum('saldo');
        
        $labaRugi = $pendapatan - $beban;
        
        return [
            'pendapatan' => $pendapatan,
            'beban' => $beban,
            'laba_rugi' => $labaRugi,
            'detail_pendapatan' => collect($neracaSaldo)->where('tipe_akun', 'pendapatan')->values()->toArray(),
            'detail_beban' => collect($neracaSaldo)->where('tipe_akun', 'beban')->values()->toArray(),
        ];
    }

    /**
     * Get Neraca dari tabel neraca_saldo (ledger)
     * Format: ASET, KEWAJIBAN, MODAL
     * Validasi: ASET = KEWAJIBAN + MODAL
     * 
     * @param int $desaId
     * @param string $periode Format: YYYY-MM (contoh: 2026-01)
     * @param int|null $unitUsahaId
     * @return array
     */
    public function getNeracaFromLedger(int $desaId, string $periode, ?int $unitUsahaId = null): array
    {
        // Query akun aset, kewajiban, dan ekuitas dari neraca_saldo
        if ($unitUsahaId !== null) {
            // Filter by unit usaha tertentu
            $query = Akun::withoutGlobalScopes()
                ->leftJoin('neraca_saldo', function ($join) use ($desaId, $periode, $unitUsahaId) {
                    $join->on('neraca_saldo.akun_id', '=', 'akun.id')
                         ->where('neraca_saldo.desa_id', '=', $desaId)
                         ->where('neraca_saldo.periode', '=', $periode)
                         ->where('neraca_saldo.unit_usaha_id', '=', $unitUsahaId);
                })
                ->where('akun.status', 'aktif')
                ->whereIn('akun.tipe_akun', ['aset', 'kewajiban', 'ekuitas'])
                ->whereNull('akun.deleted_at')
                ->select(
                    'akun.id as akun_id',
                    'akun.kode_akun',
                    'akun.nama_akun',
                    'akun.tipe_akun',
                    DB::raw('COALESCE(neraca_saldo.saldo_akhir_debit, 0) as saldo_akhir_debit'),
                    DB::raw('COALESCE(neraca_saldo.saldo_akhir_kredit, 0) as saldo_akhir_kredit')
                )
                ->orderBy('akun.kode_akun');
            
            $results = $query->get();
        } else {
            // Aggregate semua unit usaha
            $query = Akun::withoutGlobalScopes()
                ->leftJoin('neraca_saldo', function ($join) use ($desaId, $periode) {
                    $join->on('neraca_saldo.akun_id', '=', 'akun.id')
                         ->where('neraca_saldo.desa_id', '=', $desaId)
                         ->where('neraca_saldo.periode', '=', $periode);
                })
                ->where('akun.status', 'aktif')
                ->whereIn('akun.tipe_akun', ['aset', 'kewajiban', 'ekuitas'])
                ->whereNull('akun.deleted_at')
                ->select(
                    'akun.id as akun_id',
                    'akun.kode_akun',
                    'akun.nama_akun',
                    'akun.tipe_akun',
                    DB::raw('COALESCE(SUM(neraca_saldo.saldo_akhir_debit), 0) as saldo_akhir_debit'),
                    DB::raw('COALESCE(SUM(neraca_saldo.saldo_akhir_kredit), 0) as saldo_akhir_kredit')
                )
                ->groupBy('akun.id', 'akun.kode_akun', 'akun.nama_akun', 'akun.tipe_akun')
                ->orderBy('akun.kode_akun');
            
            $results = $query->get();
        }
        
        // Pisahkan aset, kewajiban, dan ekuitas
        $asetList = [];
        $kewajibanList = [];
        $modalList = [];
        
        foreach ($results as $item) {
            // Aset: normal debit, jadi saldo = saldo_akhir_debit - saldo_akhir_kredit
            // Kewajiban & Ekuitas: normal kredit, jadi saldo = saldo_akhir_kredit - saldo_akhir_debit
            if ($item->tipe_akun === 'aset') {
                $saldo = (float) $item->saldo_akhir_debit - (float) $item->saldo_akhir_kredit;
            } else {
                $saldo = (float) $item->saldo_akhir_kredit - (float) $item->saldo_akhir_debit;
            }
            
            $data = [
                'akun_id' => (int) $item->akun_id,
                'kode_akun' => $item->kode_akun,
                'nama_akun' => $item->nama_akun,
                'saldo_akhir_debit' => (float) $item->saldo_akhir_debit,
                'saldo_akhir_kredit' => (float) $item->saldo_akhir_kredit,
                'saldo' => $saldo,
            ];
            
            if ($item->tipe_akun === 'aset') {
                $asetList[] = $data;
            } elseif ($item->tipe_akun === 'kewajiban') {
                $kewajibanList[] = $data;
            } else {
                $modalList[] = $data;
            }
        }
        
        // Hitung total
        $totalAset = collect($asetList)->sum('saldo');
        $totalKewajiban = collect($kewajibanList)->sum('saldo');
        $totalModal = collect($modalList)->sum('saldo');
        $totalKewajibanModal = $totalKewajiban + $totalModal;
        
        // Validasi: ASET = KEWAJIBAN + MODAL
        $isBalanced = abs($totalAset - $totalKewajibanModal) < 0.01;
        
        return [
            'periode' => $periode,
            'aset' => $totalAset,
            'kewajiban' => $totalKewajiban,
            'modal' => $totalModal,
            'total_kewajiban_modal' => $totalKewajibanModal,
            'is_balanced' => $isBalanced,
            'selisih' => abs($totalAset - $totalKewajibanModal),
            'detail_aset' => $asetList,
            'detail_kewajiban' => $kewajibanList,
            'detail_modal' => $modalList,
        ];
    }

    /**
     * Get Perubahan Modal untuk periode tertentu
     * 
     * @param int $desaId
     * @param string $periode Format: YYYY-MM
     * @param int|null $unitUsahaId
     * @return array
     */
    public function getPerubahanModal(int $desaId, string $periode, ?int $unitUsahaId = null): array
    {
        // 1. Modal Awal: Saldo akhir ekuitas periode sebelumnya
        $previousPeriod = Carbon::createFromFormat('Y-m', $periode)
            ->subMonth()
            ->format('Y-m');
        
        // Query akun ekuitas dari periode sebelumnya
        $modalAwalQuery = Akun::withoutGlobalScopes()
            ->leftJoin('neraca_saldo', function ($join) use ($desaId, $previousPeriod, $unitUsahaId) {
                $join->on('neraca_saldo.akun_id', '=', 'akun.id')
                     ->where('neraca_saldo.desa_id', '=', $desaId)
                     ->where('neraca_saldo.periode', '=', $previousPeriod);
                
                if ($unitUsahaId !== null) {
                    $join->where('neraca_saldo.unit_usaha_id', '=', $unitUsahaId);
                } else {
                    $join->whereNull('neraca_saldo.unit_usaha_id');
                }
            })
            ->where('akun.status', 'aktif')
            ->where('akun.tipe_akun', 'ekuitas')
            ->whereNull('akun.deleted_at')
            ->select(
                DB::raw('COALESCE(neraca_saldo.saldo_akhir_debit, 0) as saldo_akhir_debit'),
                DB::raw('COALESCE(neraca_saldo.saldo_akhir_kredit, 0) as saldo_akhir_kredit')
            );
        
        $modalAwalResults = $modalAwalQuery->get();
        $modalAwal = $modalAwalResults->sum(function ($item) {
            // Ekuitas normal kredit, jadi saldo = saldo_akhir_kredit - saldo_akhir_debit
            return (float) $item->saldo_akhir_kredit - (float) $item->saldo_akhir_debit;
        });
        
        // 2. Laba Bersih: Dari laba rugi kumulatif
        $labaRugi = $this->getLabaRugiFromLedger($desaId, $periode, 'kumulatif', $unitUsahaId);
        $labaBersih = $labaRugi['laba_bersih'] ?? 0;
        
        // 3. Prive: Saldo akhir akun prive (jika ada)
        $priveQuery = Akun::withoutGlobalScopes()
            ->leftJoin('neraca_saldo', function ($join) use ($desaId, $periode, $unitUsahaId) {
                $join->on('neraca_saldo.akun_id', '=', 'akun.id')
                     ->where('neraca_saldo.desa_id', '=', $desaId)
                     ->where('neraca_saldo.periode', '=', $periode);
                
                if ($unitUsahaId !== null) {
                    $join->where('neraca_saldo.unit_usaha_id', '=', $unitUsahaId);
                } else {
                    $join->whereNull('neraca_saldo.unit_usaha_id');
                }
            })
            ->where('akun.status', 'aktif')
            ->where(function ($q) {
                $q->where('akun.nama_akun', 'like', '%prive%')
                  ->orWhere('akun.kode_akun', 'like', '%prive%');
            })
            ->whereNull('akun.deleted_at')
            ->select(
                'akun.id as akun_id',
                'akun.kode_akun',
                'akun.nama_akun',
                DB::raw('COALESCE(neraca_saldo.saldo_akhir_debit, 0) as saldo_akhir_debit'),
                DB::raw('COALESCE(neraca_saldo.saldo_akhir_kredit, 0) as saldo_akhir_kredit')
            );
        
        $priveList = $priveQuery->get()->map(function ($item) {
            $saldo = (float) $item->saldo_akhir_debit - (float) $item->saldo_akhir_kredit;
            return [
                'akun_id' => (int) $item->akun_id,
                'kode_akun' => $item->kode_akun,
                'nama_akun' => $item->nama_akun,
                'saldo' => $saldo,
            ];
        })->toArray();
        
        $totalPrive = collect($priveList)->sum('saldo');
        
        // 4. Modal Akhir: Modal Awal + Laba Bersih + Prive
        $modalAkhir = $modalAwal + $labaBersih + $totalPrive;
        
        return [
            'periode' => $periode,
            'modal_awal' => (float) $modalAwal,
            'laba_bersih' => (float) $labaBersih,
            'prive' => (float) $totalPrive,
            'modal_akhir' => (float) $modalAkhir,
            'detail_prive' => $priveList,
        ];
    }

    /**
     * Hitung neraca (balance sheet) pada tanggal tertentu (LEGACY - dari jurnal)
     * 
     * @deprecated Gunakan getNeracaFromLedger() untuk format lengkap
     * @param int $desaId
     * @param string $tanggal (Y-m-d)
     * @param int|null $unitUsahaId
     * @return array
     */
    public function getNeraca(int $desaId, string $tanggal, ?int $unitUsahaId = null): array
    {
        $query = JurnalDetail::query()
            ->join('jurnal', 'jurnal_detail.jurnal_id', '=', 'jurnal.id')
            ->join('akun', 'jurnal_detail.akun_id', '=', 'akun.id')
            ->where('jurnal.desa_id', $desaId)
            ->where('jurnal.status', 'posted')
            ->where('jurnal.tanggal_transaksi', '<=', $tanggal);
        
        if ($unitUsahaId) {
            $query->where('jurnal.unit_usaha_id', $unitUsahaId);
        }
        
        $results = $query->select(
            'akun.id',
            'akun.kode_akun',
            'akun.nama_akun',
            'akun.tipe_akun',
            DB::raw("SUM(CASE WHEN jurnal_detail.posisi = 'debit' THEN jurnal_detail.jumlah ELSE 0 END) as total_debit"),
            DB::raw("SUM(CASE WHEN jurnal_detail.posisi = 'kredit' THEN jurnal_detail.jumlah ELSE 0 END) as total_kredit")
        )
        ->groupBy('akun.id', 'akun.kode_akun', 'akun.nama_akun', 'akun.tipe_akun')
        ->orderBy('akun.kode_akun')
        ->get();
        
        // Hitung saldo per akun
        $saldoAkun = $results->map(function ($item) {
            $normalDebit = in_array($item->tipe_akun, ['aset', 'beban']);
            
            if ($normalDebit) {
                $saldo = $item->total_debit - $item->total_kredit;
            } else {
                $saldo = $item->total_kredit - $item->total_debit;
            }
            
            return [
                'akun_id' => $item->id,
                'kode_akun' => $item->kode_akun,
                'nama_akun' => $item->nama_akun,
                'tipe_akun' => $item->tipe_akun,
                'saldo' => $saldo,
            ];
        });
        
        $aset = $saldoAkun->where('tipe_akun', 'aset')->sum('saldo');
        $kewajiban = $saldoAkun->where('tipe_akun', 'kewajiban')->sum('saldo');
        $ekuitas = $saldoAkun->where('tipe_akun', 'ekuitas')->sum('saldo');
        
        return [
            'aset' => $aset,
            'kewajiban' => $kewajiban,
            'ekuitas' => $ekuitas,
            'detail_aset' => $saldoAkun->where('tipe_akun', 'aset')->values()->toArray(),
            'detail_kewajiban' => $saldoAkun->where('tipe_akun', 'kewajiban')->values()->toArray(),
            'detail_ekuitas' => $saldoAkun->where('tipe_akun', 'ekuitas')->values()->toArray(),
        ];
    }

    /**
     * Validasi data jurnal
     */
    protected function validateJurnalData(array $data): void
    {
        if (empty($data['details']) || count($data['details']) < 2) {
            throw ValidationException::withMessages([
                'details' => 'Jurnal harus memiliki minimal 2 baris (debit dan kredit).',
            ]);
        }
        
        // Validasi akun exists
        foreach ($data['details'] as $detail) {
            $akun = Akun::find($detail['akun_id']);
            if (!$akun) {
                throw ValidationException::withMessages([
                    'akun_id' => "Akun dengan ID {$detail['akun_id']} tidak ditemukan.",
                ]);
            }
            
            if ($akun->status !== 'aktif') {
                throw ValidationException::withMessages([
                    'akun_id' => "Akun {$akun->nama_akun} tidak aktif.",
                ]);
            }
        }
    }

    /**
     * Validasi balance (debit = kredit)
     */
    protected function validateBalance(array $details): void
    {
        $totals = $this->calculateTotals($details);
        
        // Gunakan bccomp untuk perbandingan decimal yang akurat
        if (bccomp($totals['debit'], $totals['kredit'], 2) !== 0) {
            throw ValidationException::withMessages([
                'balance' => sprintf(
                    'Jurnal tidak balance. Debit: %s, Kredit: %s',
                    number_format($totals['debit'], 2),
                    number_format($totals['kredit'], 2)
                ),
            ]);
        }
    }

    /**
     * Hitung total debit dan kredit
     */
    protected function calculateTotals(array $details): array
    {
        $totalDebit = '0';
        $totalKredit = '0';
        
        foreach ($details as $detail) {
            if ($detail['posisi'] === 'debit') {
                $totalDebit = bcadd($totalDebit, $detail['jumlah'], 2);
            } else {
                $totalKredit = bcadd($totalKredit, $detail['jumlah'], 2);
            }
        }
        
        return [
            'debit' => $totalDebit,
            'kredit' => $totalKredit,
        ];
    }

    /**
     * Post transaksi jurnal ke neraca saldo (ledger)
     * Dipanggil otomatis saat jurnal di-post
     * 
     * @param Jurnal $jurnal
     * @return void
     */
    public function postToLedger(Jurnal $jurnal): void
    {
        if ($jurnal->status !== 'posted') {
            throw ValidationException::withMessages([
                'status' => 'Hanya jurnal dengan status posted yang dapat di-post ke ledger.',
            ]);
        }

        $periode = Carbon::parse($jurnal->tanggal_transaksi)->format('Y-m');

        DB::transaction(function () use ($jurnal, $periode) {
            foreach ($jurnal->details as $detail) {
                $this->updateOrCreateNeracaSaldo(
                    $jurnal->desa_id,
                    $detail->akun_id,
                    $periode,
                    $jurnal->unit_usaha_id,
                    $detail->posisi,
                    $detail->jumlah
                );
            }
        });
    }

    /**
     * Recalculate balance untuk periode tertentu
     * Berguna saat ada koreksi atau perlu recalculate
     * 
     * @param int $desaId
     * @param string $periode (Y-m format)
     * @param int|null $unitUsahaId
     * @return void
     */
    public function recalculateBalance(int $desaId, string $periode, ?int $unitUsahaId = null): void
    {
        DB::transaction(function () use ($desaId, $periode, $unitUsahaId) {
            // Hapus neraca saldo existing untuk periode ini
            $query = NeracaSaldo::where('desa_id', $desaId)
                ->where('periode', $periode);
            
            if ($unitUsahaId) {
                $query->where('unit_usaha_id', $unitUsahaId);
            }
            
            $query->delete();

            // Ambil semua jurnal posted untuk periode ini
            $jurnalQuery = Jurnal::where('desa_id', $desaId)
                ->where('status', 'posted')
                ->whereYear('tanggal_transaksi', substr($periode, 0, 4))
                ->whereMonth('tanggal_transaksi', substr($periode, 5, 2));
            
            if ($unitUsahaId) {
                $jurnalQuery->where('unit_usaha_id', $unitUsahaId);
            }

            $jurnals = $jurnalQuery->with('details')->get();

            // Re-post setiap jurnal
            foreach ($jurnals as $jurnal) {
                foreach ($jurnal->details as $detail) {
                    $this->updateOrCreateNeracaSaldo(
                        $desaId,
                        $detail->akun_id,
                        $periode,
                        $jurnal->unit_usaha_id,
                        $detail->posisi,
                        $detail->jumlah
                    );
                }
            }

            // Update saldo akhir
            $this->calculateSaldoAkhir($desaId, $periode, $unitUsahaId);
        });
    }

    /**
     * Close periode akuntansi
     * Setelah close, periode tidak bisa diubah
     * 
     * @param int $desaId
     * @param string $periode
     * @param int|null $unitUsahaId
     * @return void
     */
    public function closePeriod(int $desaId, string $periode, ?int $unitUsahaId = null): void
    {
        // Validasi tidak ada jurnal draft di periode ini
        $hasDraft = Jurnal::where('desa_id', $desaId)
            ->where('status', 'draft')
            ->whereYear('tanggal_transaksi', substr($periode, 0, 4))
            ->whereMonth('tanggal_transaksi', substr($periode, 5, 2))
            ->when($unitUsahaId, fn($q) => $q->where('unit_usaha_id', $unitUsahaId))
            ->exists();

        if ($hasDraft) {
            throw ValidationException::withMessages([
                'draft' => 'Tidak dapat menutup periode. Masih ada jurnal dengan status draft.',
            ]);
        }

        DB::transaction(function () use ($desaId, $periode, $unitUsahaId) {
            // Recalculate untuk memastikan data akurat
            $this->recalculateBalance($desaId, $periode, $unitUsahaId);

            // Update status periode menjadi closed
            NeracaSaldo::where('desa_id', $desaId)
                ->where('periode', $periode)
                ->when($unitUsahaId, fn($q) => $q->where('unit_usaha_id', $unitUsahaId))
                ->update([
                    'status_periode' => 'closed',
                    'closed_at' => now(),
                    'closed_by' => Auth::id(),
                ]);

            // Buat saldo awal untuk periode berikutnya
            $this->createNextPeriodOpeningBalance($desaId, $periode, $unitUsahaId);
        });
    }

    /**
     * Check apakah periode sudah closed
     * 
     * @param int $desaId
     * @param string $periode Format: YYYY-MM
     * @param int|null $unitUsahaId
     * @return bool
     */
    public function isPeriodClosed(int $desaId, string $periode, ?int $unitUsahaId = null): bool
    {
        $query = NeracaSaldo::where('desa_id', $desaId)
            ->where('periode', $periode)
            ->where('status_periode', 'closed');
        
        if ($unitUsahaId !== null) {
            $query->where('unit_usaha_id', $unitUsahaId);
        } else {
            $query->whereNull('unit_usaha_id');
        }
        
        // Jika ada minimal 1 record dengan status closed, berarti periode closed
        return $query->exists();
    }

    /**
     * Reopen periode yang sudah closed
     * Hanya untuk koreksi/adjustment
     * 
     * @param int $desaId
     * @param string $periode
     * @param int|null $unitUsahaId
     * @return void
     */
    public function reopenPeriod(int $desaId, string $periode, ?int $unitUsahaId = null): void
    {
        DB::transaction(function () use ($desaId, $periode, $unitUsahaId) {
            NeracaSaldo::where('desa_id', $desaId)
                ->where('periode', $periode)
                ->when($unitUsahaId, fn($q) => $q->where('unit_usaha_id', $unitUsahaId))
                ->update([
                    'status_periode' => 'open',
                    'closed_at' => null,
                    'closed_by' => null,
                ]);
        });
    }

    /**
     * Helper: Update atau create neraca saldo
     */
    protected function updateOrCreateNeracaSaldo(
        int $desaId,
        int $akunId,
        string $periode,
        ?int $unitUsahaId,
        string $posisi,
        float $jumlah
    ): void {
        $neracaSaldo = NeracaSaldo::firstOrNew([
            'desa_id' => $desaId,
            'akun_id' => $akunId,
            'periode' => $periode,
            'unit_usaha_id' => $unitUsahaId,
        ]);

        // Update mutasi
        if ($posisi === 'debit') {
            $neracaSaldo->mutasi_debit = bcadd(
                (string)($neracaSaldo->mutasi_debit ?? '0'), 
                (string)$jumlah, 
                2
            );
        } else {
            $neracaSaldo->mutasi_kredit = bcadd(
                (string)($neracaSaldo->mutasi_kredit ?? '0'), 
                (string)$jumlah, 
                2
            );
        }

        // Set saldo awal dari periode sebelumnya (jika belum ada)
        if (!$neracaSaldo->exists) {
            $this->setSaldoAwal($neracaSaldo, $desaId, $akunId, $periode, $unitUsahaId);
        }

        // Hitung saldo akhir
        $neracaSaldo->saldo_akhir_debit = bcadd(
            bcadd(
                (string)($neracaSaldo->saldo_awal_debit ?? '0'), 
                (string)($neracaSaldo->mutasi_debit ?? '0'), 
                2
            ),
            '0',
            2
        );
        $neracaSaldo->saldo_akhir_kredit = bcadd(
            bcadd(
                (string)($neracaSaldo->saldo_awal_kredit ?? '0'), 
                (string)($neracaSaldo->mutasi_kredit ?? '0'), 
                2
            ),
            '0',
            2
        );

        $neracaSaldo->created_by = $neracaSaldo->created_by ?? Auth::id();
        $neracaSaldo->updated_by = Auth::id();
        $neracaSaldo->save();
    }

    /**
     * Helper: Set saldo awal dari periode sebelumnya
     */
    protected function setSaldoAwal(
        NeracaSaldo $neracaSaldo,
        int $desaId,
        int $akunId,
        string $periode,
        ?int $unitUsahaId
    ): void {
        // Ambil periode sebelumnya
        $previousPeriod = Carbon::createFromFormat('Y-m', $periode)
            ->subMonth()
            ->format('Y-m');

        $previous = NeracaSaldo::where('desa_id', $desaId)
            ->where('akun_id', $akunId)
            ->where('periode', $previousPeriod)
            ->when($unitUsahaId, fn($q) => $q->where('unit_usaha_id', $unitUsahaId))
            ->first();

        if ($previous) {
            $neracaSaldo->saldo_awal_debit = $previous->saldo_akhir_debit;
            $neracaSaldo->saldo_awal_kredit = $previous->saldo_akhir_kredit;
        } else {
            $neracaSaldo->saldo_awal_debit = 0;
            $neracaSaldo->saldo_awal_kredit = 0;
        }
    }

    /**
     * Helper: Calculate saldo akhir untuk semua akun di periode
     */
    protected function calculateSaldoAkhir(int $desaId, string $periode, ?int $unitUsahaId): void
    {
        $neracaSaldos = NeracaSaldo::where('desa_id', $desaId)
            ->where('periode', $periode)
            ->when($unitUsahaId, fn($q) => $q->where('unit_usaha_id', $unitUsahaId))
            ->get();

        foreach ($neracaSaldos as $ns) {
            $ns->saldo_akhir_debit = bcadd(
                bcadd($ns->saldo_awal_debit, $ns->mutasi_debit, 2),
                '0',
                2
            );
            $ns->saldo_akhir_kredit = bcadd(
                bcadd($ns->saldo_awal_kredit, $ns->mutasi_kredit, 2),
                '0',
                2
            );
            $ns->save();
        }
    }

    /**
     * Helper: Create opening balance untuk periode berikutnya
     */
    protected function createNextPeriodOpeningBalance(int $desaId, string $periode, ?int $unitUsahaId): void
    {
        $nextPeriod = Carbon::createFromFormat('Y-m', $periode)
            ->addMonth()
            ->format('Y-m');

        $currentBalances = NeracaSaldo::where('desa_id', $desaId)
            ->where('periode', $periode)
            ->when($unitUsahaId, fn($q) => $q->where('unit_usaha_id', $unitUsahaId))
            ->get();

        foreach ($currentBalances as $balance) {
            NeracaSaldo::updateOrCreate(
                [
                    'desa_id' => $desaId,
                    'akun_id' => $balance->akun_id,
                    'periode' => $nextPeriod,
                    'unit_usaha_id' => $unitUsahaId,
                ],
                [
                    'saldo_awal_debit' => $balance->saldo_akhir_debit,
                    'saldo_awal_kredit' => $balance->saldo_akhir_kredit,
                    'mutasi_debit' => 0,
                    'mutasi_kredit' => 0,
                    'saldo_akhir_debit' => $balance->saldo_akhir_debit,
                    'saldo_akhir_kredit' => $balance->saldo_akhir_kredit,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]
            );
        }
    }
}
