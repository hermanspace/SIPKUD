<?php

namespace App\Services;

use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\NeracaSaldo;
use App\Models\Pinjaman;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * KolektibilitasService
 *
 * Klasifikasi kualitas pinjaman (lancar / kurang lancar / diragukan / macet)
 * dan penyisihan piutang tak tertagih (PPAP) - mengikuti konvensi penilaian
 * kesehatan KSP/USP. Parameter di config/accounting.php.
 */
class KolektibilitasService
{
    public const KATEGORI = ['lancar', 'kurang_lancar', 'diragukan', 'macet'];

    /**
     * Ringkasan kolektibilitas pinjaman aktif.
     *
     * @return array{kategori: array, total_sisa: float, total_penyisihan: float, npl_persen: float, pinjaman: Collection}
     */
    public function ringkasan(?int $desaId = null, ?int $kecamatanId = null): array
    {
        $pinjaman = Pinjaman::withoutGlobalScopes()
            ->with(['anggota', 'desa'])
            ->where('status_pinjaman', 'aktif')
            ->when($desaId, fn ($q) => $q->where('desa_id', $desaId))
            ->when(! $desaId && $kecamatanId, fn ($q) => $q->whereHas(
                'desa', fn ($d) => $d->where('kecamatan_id', $kecamatanId)
            ))
            ->get();

        $rates = config('accounting.penyisihan');

        $kategori = collect(self::KATEGORI)->mapWithKeys(fn ($k) => [$k => [
            'jumlah' => 0,
            'sisa' => 0.0,
            'rate' => $rates[$k],
            'penyisihan' => 0.0,
        ]])->all();

        foreach ($pinjaman as $p) {
            $k = $p->kolektibilitas;
            $kategori[$k]['jumlah']++;
            $kategori[$k]['sisa'] += $p->sisa_pinjaman;
            $kategori[$k]['penyisihan'] += $p->sisa_pinjaman * $rates[$k] / 100;
        }

        $totalSisa = array_sum(array_column($kategori, 'sisa'));
        $sisaBermasalah = $kategori['kurang_lancar']['sisa']
            + $kategori['diragukan']['sisa']
            + $kategori['macet']['sisa'];

        return [
            'kategori' => $kategori,
            'total_sisa' => $totalSisa,
            'total_penyisihan' => array_sum(array_column($kategori, 'penyisihan')),
            'npl_persen' => $totalSisa > 0 ? $sisaBermasalah / $totalSisa * 100 : 0.0,
            'pinjaman' => $pinjaman->sortByDesc('tunggakan_bulan')->values(),
        ];
    }

    /**
     * Buat jurnal penyesuaian penyisihan piutang untuk sebuah desa.
     *
     * Jurnal dibuat sebesar SELISIH antara target penyisihan (berdasar
     * kolektibilitas saat ini) dan saldo akun Cadangan Kerugian Piutang di
     * ledger, sehingga cadangan selalu mencerminkan kondisi terkini.
     *
     * @return array{jurnal: Jurnal|null, target: float, saldo_sebelum: float, penyesuaian: float}
     */
    public function buatJurnalPenyisihan(int $desaId, ?string $periode = null): array
    {
        $periode ??= now()->format('Y-m');

        $akunBeban = Akun::aktif()->where('nama_akun', config('accounting.akun_penyisihan.beban'))->first();
        $akunCadangan = Akun::aktif()->where('nama_akun', config('accounting.akun_penyisihan.cadangan'))->first();

        if (! $akunBeban || ! $akunCadangan) {
            throw ValidationException::withMessages([
                'akun' => sprintf(
                    'Akun "%s" / "%s" tidak ditemukan di COA. Tambahkan dulu di Master Akun.',
                    config('accounting.akun_penyisihan.beban'),
                    config('accounting.akun_penyisihan.cadangan')
                ),
            ]);
        }

        $target = $this->ringkasan($desaId)['total_penyisihan'];

        // Saldo cadangan saat ini dari ledger (kontra aset: normal kredit)
        $ledger = NeracaSaldo::withoutGlobalScopes()
            ->where('desa_id', $desaId)
            ->where('akun_id', $akunCadangan->id)
            ->where('periode', $periode)
            ->get();

        $saldoSebelum = (float) $ledger->sum('saldo_akhir_kredit') - (float) $ledger->sum('saldo_akhir_debit');
        $penyesuaian = round($target - $saldoSebelum, 2);

        if (abs($penyesuaian) < 0.01) {
            return [
                'jurnal' => null,
                'target' => $target,
                'saldo_sebelum' => $saldoSebelum,
                'penyesuaian' => 0.0,
            ];
        }

        $tanggal = min(now(), Carbon::createFromFormat('Y-m', $periode)->endOfMonth())->toDateString();

        $details = $penyesuaian > 0
            ? [
                ['akun_id' => $akunBeban->id, 'posisi' => 'debit', 'jumlah' => $penyesuaian, 'keterangan' => 'Penyisihan kerugian piutang'],
                ['akun_id' => $akunCadangan->id, 'posisi' => 'kredit', 'jumlah' => $penyesuaian, 'keterangan' => 'Cadangan kerugian piutang'],
            ]
            : [
                ['akun_id' => $akunCadangan->id, 'posisi' => 'debit', 'jumlah' => abs($penyesuaian), 'keterangan' => 'Pemulihan cadangan kerugian piutang'],
                ['akun_id' => $akunBeban->id, 'posisi' => 'kredit', 'jumlah' => abs($penyesuaian), 'keterangan' => 'Pemulihan beban penyisihan'],
            ];

        $jurnal = app(AccountingService::class)->createJurnal([
            'desa_id' => $desaId,
            'tanggal_transaksi' => $tanggal,
            'jenis_jurnal' => 'penyesuaian',
            'keterangan' => "Penyisihan piutang tak tertagih periode {$periode} (berdasar kolektibilitas)",
            'status' => 'posted',
            'details' => $details,
        ]);

        return [
            'jurnal' => $jurnal,
            'target' => $target,
            'saldo_sebelum' => $saldoSebelum,
            'penyesuaian' => $penyesuaian,
        ];
    }
}
