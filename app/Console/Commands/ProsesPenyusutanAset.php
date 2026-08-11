<?php

namespace App\Console\Commands;

use App\Models\AsetTetap;
use App\Services\AsetTetapService;
use Illuminate\Console\Command;

/**
 * Penyusutan aset tetap bulanan untuk seluruh desa yang memiliki aset aktif.
 * Dijadwalkan tiap awal bulan (routes/console.php); aman diulang (idempoten).
 */
class ProsesPenyusutanAset extends Command
{
    protected $signature = 'aset:penyusutan {--periode= : Periode YYYY-MM, default bulan berjalan}';

    protected $description = 'Buat jurnal penyusutan bulanan seluruh aset tetap aktif';

    public function handle(AsetTetapService $service): int
    {
        $periode = $this->option('periode') ?: now()->format('Y-m');

        $desaIds = AsetTetap::withoutGlobalScopes()
            ->where('status', 'aktif')
            ->distinct()
            ->pluck('desa_id');

        $totalAset = 0;
        foreach ($desaIds as $desaId) {
            $hasil = $service->prosesPenyusutan($desaId, $periode);
            $totalAset += $hasil['diproses'];

            if ($hasil['diproses'] > 0) {
                $this->info(sprintf(
                    'Desa %d: %d aset disusutkan, total Rp %s',
                    $desaId,
                    $hasil['diproses'],
                    number_format($hasil['total'], 2, ',', '.')
                ));
            }
        }

        $this->info("Selesai - {$totalAset} aset diproses untuk periode {$periode}.");

        return self::SUCCESS;
    }
}
