<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Backup database penuh via aplikasi (memakai DatabaseBackupService yang
 * sama dengan panel Super Admin). Dijadwalkan harian di routes/console.php.
 */
class BackupDatabase extends Command
{
    protected $signature = 'db:backup
                            {--keep=14 : Jumlah backup terbaru yang disimpan (sisanya dihapus)}';

    protected $description = 'Buat backup database penuh ke storage/app/backups';

    public function handle(DatabaseBackupService $backup): int
    {
        try {
            $result = $backup->create('scheduled');
        } catch (Throwable $e) {
            $this->error('Backup gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Backup berhasil: %s (%s)',
            $result['filename'],
            $this->formatBytes($result['size'])
        ));

        $deleted = $backup->prune((int) $this->option('keep'));
        if ($deleted > 0) {
            $this->info("Retensi: {$deleted} backup lama dihapus.");
        }

        return self::SUCCESS;
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = $bytes > 0 ? min((int) floor(log($bytes, 1024)), count($units) - 1) : 0;

        return round($bytes / (1024 ** $i), 2).' '.$units[$i];
    }
}
