<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Verifikasi integritas data akuntansi double entry.
 *
 * Pemeriksaan:
 *  1. Jurnal aktif (non-void) yang tidak balance (total_debit != total_kredit)
 *  2. Total header jurnal tidak sama dengan penjumlahan baris detailnya
 *  3. Mutasi neraca_saldo tidak sama dengan penjumlahan jurnal posted
 *  4. Saldo akhir neraca_saldo tidak sama dengan saldo awal + mutasi
 *
 * Dirancang untuk dijalankan terjadwal (scheduler) maupun manual.
 * Exit code 1 jika ditemukan ketidaksesuaian - bisa dipakai untuk alerting.
 */
class VerifyAccountingIntegrity extends Command
{
    protected $signature = 'accounting:verify-integrity
                            {--desa= : Batasi pemeriksaan pada satu desa (ID)}
                            {--periode= : Batasi pemeriksaan pada satu periode (YYYY-MM)}';

    protected $description = 'Verifikasi integritas akuntansi: balance jurnal, konsistensi detail, dan neraca saldo';

    /** Toleransi pembulatan desimal (2 digit) */
    private const EPSILON = 0.005;

    public function handle(): int
    {
        $issues = [];

        $issues = array_merge($issues, $this->checkUnbalancedJurnal());
        $issues = array_merge($issues, $this->checkHeaderVsDetail());
        $issues = array_merge($issues, $this->checkLedgerVsJurnal());
        $issues = array_merge($issues, $this->checkSaldoAkhir());

        if (empty($issues)) {
            $this->info('Integritas akuntansi OK - tidak ditemukan ketidaksesuaian.');

            return self::SUCCESS;
        }

        $this->error(sprintf('Ditemukan %d ketidaksesuaian integritas akuntansi:', count($issues)));
        $this->table(['Pemeriksaan', 'Referensi', 'Keterangan'], $issues);

        Log::warning('Verifikasi integritas akuntansi menemukan ketidaksesuaian', [
            'jumlah' => count($issues),
            'issues' => $issues,
        ]);

        return self::FAILURE;
    }

    /**
     * Check 1: jurnal aktif yang tidak balance.
     */
    private function checkUnbalancedJurnal(): array
    {
        $query = DB::table('jurnal')
            ->whereNull('deleted_at')
            ->where('status', '!=', 'void')
            ->whereRaw('ABS(total_debit - total_kredit) > '.self::EPSILON);

        if ($desa = $this->option('desa')) {
            $query->where('desa_id', $desa);
        }

        return $query->get(['id', 'nomor_jurnal', 'desa_id', 'total_debit', 'total_kredit'])
            ->map(fn ($j) => [
                'jurnal-tidak-balance',
                sprintf('jurnal #%d (%s, desa %d)', $j->id, $j->nomor_jurnal, $j->desa_id),
                sprintf('debit %s != kredit %s', $j->total_debit, $j->total_kredit),
            ])
            ->all();
    }

    /**
     * Check 2: total header jurnal vs penjumlahan detail.
     */
    private function checkHeaderVsDetail(): array
    {
        $query = DB::table('jurnal')
            ->leftJoin('jurnal_detail', 'jurnal_detail.jurnal_id', '=', 'jurnal.id')
            ->whereNull('jurnal.deleted_at')
            ->where('jurnal.status', '!=', 'void')
            ->groupBy('jurnal.id', 'jurnal.nomor_jurnal', 'jurnal.desa_id', 'jurnal.total_debit', 'jurnal.total_kredit')
            ->havingRaw(
                "ABS(jurnal.total_debit - COALESCE(SUM(CASE WHEN jurnal_detail.posisi = 'debit' THEN jurnal_detail.jumlah ELSE 0 END), 0)) > ".self::EPSILON."
                 OR ABS(jurnal.total_kredit - COALESCE(SUM(CASE WHEN jurnal_detail.posisi = 'kredit' THEN jurnal_detail.jumlah ELSE 0 END), 0)) > ".self::EPSILON
            );

        if ($desa = $this->option('desa')) {
            $query->where('jurnal.desa_id', $desa);
        }

        return $query->get([
            'jurnal.id',
            'jurnal.nomor_jurnal',
            'jurnal.desa_id',
            'jurnal.total_debit',
            'jurnal.total_kredit',
        ])
            ->map(fn ($j) => [
                'header-vs-detail',
                sprintf('jurnal #%d (%s, desa %d)', $j->id, $j->nomor_jurnal, $j->desa_id),
                'total header tidak sama dengan penjumlahan baris detail',
            ])
            ->all();
    }

    /**
     * Check 3: mutasi neraca_saldo vs penjumlahan jurnal posted per akun/periode.
     */
    private function checkLedgerVsJurnal(): array
    {
        $periodeExpr = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', jurnal.tanggal_transaksi)"
            : "to_char(jurnal.tanggal_transaksi, 'YYYY-MM')";

        $jurnalQuery = DB::table('jurnal_detail')
            ->join('jurnal', 'jurnal.id', '=', 'jurnal_detail.jurnal_id')
            ->whereNull('jurnal.deleted_at')
            ->where('jurnal.status', 'posted')
            ->groupBy('jurnal.desa_id', 'jurnal.unit_usaha_id', 'jurnal_detail.akun_id', DB::raw($periodeExpr))
            ->select([
                'jurnal.desa_id',
                'jurnal.unit_usaha_id',
                'jurnal_detail.akun_id',
                DB::raw("{$periodeExpr} as periode"),
                DB::raw("SUM(CASE WHEN jurnal_detail.posisi = 'debit' THEN jurnal_detail.jumlah ELSE 0 END) as sum_debit"),
                DB::raw("SUM(CASE WHEN jurnal_detail.posisi = 'kredit' THEN jurnal_detail.jumlah ELSE 0 END) as sum_kredit"),
            ]);

        $ledgerQuery = DB::table('neraca_saldo');

        if ($desa = $this->option('desa')) {
            $jurnalQuery->where('jurnal.desa_id', $desa);
            $ledgerQuery->where('desa_id', $desa);
        }

        if ($periode = $this->option('periode')) {
            $jurnalQuery->havingRaw("{$periodeExpr} = ?", [$periode]);
            $ledgerQuery->where('periode', $periode);
        }

        $key = fn ($desaId, $unitId, $akunId, $periode) => implode('|', [$desaId, $unitId ?? 'null', $akunId, $periode]);

        $jurnalSums = [];
        foreach ($jurnalQuery->get() as $row) {
            $jurnalSums[$key($row->desa_id, $row->unit_usaha_id, $row->akun_id, $row->periode)] = $row;
        }

        $issues = [];
        $seenLedgerKeys = [];

        foreach ($ledgerQuery->get() as $ledger) {
            $k = $key($ledger->desa_id, $ledger->unit_usaha_id, $ledger->akun_id, $ledger->periode);
            $seenLedgerKeys[$k] = true;

            $expectedDebit = isset($jurnalSums[$k]) ? (float) $jurnalSums[$k]->sum_debit : 0.0;
            $expectedKredit = isset($jurnalSums[$k]) ? (float) $jurnalSums[$k]->sum_kredit : 0.0;

            if (abs((float) $ledger->mutasi_debit - $expectedDebit) > self::EPSILON
                || abs((float) $ledger->mutasi_kredit - $expectedKredit) > self::EPSILON) {
                $issues[] = [
                    'ledger-vs-jurnal',
                    sprintf('neraca_saldo desa %d akun %d periode %s', $ledger->desa_id, $ledger->akun_id, $ledger->periode),
                    sprintf(
                        'mutasi ledger D/K %s/%s != jurnal %s/%s',
                        $ledger->mutasi_debit,
                        $ledger->mutasi_kredit,
                        number_format($expectedDebit, 2, '.', ''),
                        number_format($expectedKredit, 2, '.', '')
                    ),
                ];
            }
        }

        // Jurnal posted yang belum tercermin sama sekali di neraca_saldo
        foreach ($jurnalSums as $k => $row) {
            if (! isset($seenLedgerKeys[$k])) {
                $issues[] = [
                    'ledger-vs-jurnal',
                    sprintf('desa %d akun %d periode %s', $row->desa_id, $row->akun_id, $row->periode),
                    'jurnal posted belum diposting ke neraca_saldo',
                ];
            }
        }

        return $issues;
    }

    /**
     * Check 4: saldo akhir = saldo awal + mutasi pada neraca_saldo.
     */
    private function checkSaldoAkhir(): array
    {
        $query = DB::table('neraca_saldo')
            ->whereRaw(
                'ABS(saldo_akhir_debit - (saldo_awal_debit + mutasi_debit)) > '.self::EPSILON.'
                 OR ABS(saldo_akhir_kredit - (saldo_awal_kredit + mutasi_kredit)) > '.self::EPSILON
            );

        if ($desa = $this->option('desa')) {
            $query->where('desa_id', $desa);
        }

        if ($periode = $this->option('periode')) {
            $query->where('periode', $periode);
        }

        return $query->get(['id', 'desa_id', 'akun_id', 'periode'])
            ->map(fn ($ns) => [
                'saldo-akhir',
                sprintf('neraca_saldo #%d (desa %d, akun %d, %s)', $ns->id, $ns->desa_id, $ns->akun_id, $ns->periode),
                'saldo akhir tidak sama dengan saldo awal + mutasi',
            ])
            ->all();
    }
}
