<?php

namespace App\Services;

use App\Models\AsetTetap;
use Carbon\Carbon;

/**
 * AsetTetapService
 *
 * Penyusutan garis lurus bulanan: satu jurnal penyesuaian per aset per
 * periode (Debit Beban Penyusutan / Kredit Akumulasi Penyusutan).
 * Idempoten - aset yang sudah disusutkan untuk periode tersebut dilewati.
 */
class AsetTetapService
{
    public function __construct(protected AccountingService $accounting) {}

    /**
     * Proses penyusutan seluruh aset aktif sebuah desa untuk satu periode.
     *
     * @param  string|null  $periode  YYYY-MM, default periode berjalan
     * @return array{diproses: int, total: float}
     */
    public function prosesPenyusutan(int $desaId, ?string $periode = null): array
    {
        $periode ??= now()->format('Y-m');
        $tanggal = min(now(), Carbon::createFromFormat('Y-m', $periode)->endOfMonth())->toDateString();

        $asets = AsetTetap::withoutGlobalScopes()
            ->where('desa_id', $desaId)
            ->where('status', 'aktif')
            ->get();

        $diproses = 0;
        $total = 0.0;

        foreach ($asets as $aset) {
            // Lewati bila sudah disusutkan untuk periode ini / nilai habis /
            // perolehan setelah periode berjalan
            if ($aset->periode_penyusutan_terakhir >= $periode
                || $aset->sisa_disusutkan < 0.01
                || $aset->tanggal_perolehan->format('Y-m') > $periode) {
                continue;
            }

            $jumlah = min($aset->penyusutan_bulanan, $aset->sisa_disusutkan);
            if ($jumlah < 0.01) {
                continue;
            }

            $this->accounting->createJurnal([
                'desa_id' => $desaId,
                'unit_usaha_id' => $aset->unit_usaha_id,
                'tanggal_transaksi' => $tanggal,
                'jenis_jurnal' => 'penyesuaian',
                'keterangan' => "Penyusutan {$aset->nama_aset} periode {$periode}",
                'status' => 'posted',
                'details' => [
                    ['akun_id' => $aset->akun_beban_id, 'posisi' => 'debit', 'jumlah' => $jumlah, 'keterangan' => 'Beban penyusutan'],
                    ['akun_id' => $aset->akun_akumulasi_id, 'posisi' => 'kredit', 'jumlah' => $jumlah, 'keterangan' => 'Akumulasi penyusutan'],
                ],
            ]);

            $aset->update([
                'akumulasi_tercatat' => (float) $aset->akumulasi_tercatat + $jumlah,
                'periode_penyusutan_terakhir' => $periode,
            ]);

            $diproses++;
            $total += $jumlah;
        }

        return ['diproses' => $diproses, 'total' => $total];
    }
}
