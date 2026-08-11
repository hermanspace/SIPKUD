<?php

namespace App\Services;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * DatabaseBackupService
 *
 * Backup & restore database penuh dari aplikasi (panel Super Admin dan
 * command terjadwal). Mendukung PostgreSQL (pg_dump/pg_restore, format
 * custom .dump) dan MySQL/MariaDB (mysqldump, format .sql.gz).
 *
 * File backup disimpan di storage/app/backups (private, tidak dapat
 * diakses publik). Restore selalu membuat safety snapshot terlebih dahulu.
 */
class DatabaseBackupService
{
    /** Timeout proses dump/restore (detik) */
    protected const PROCESS_TIMEOUT = 900;

    /** Pola nama file yang diizinkan (anti path traversal) */
    protected const FILENAME_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9._-]*\.(dump|sql\.gz)$/';

    /**
     * Direktori penyimpanan backup.
     * Bisa dioverride via config 'database_backup.directory' (dipakai test).
     */
    public function directory(): string
    {
        $dir = config('database_backup.directory') ?? storage_path('app/backups');

        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        return $dir;
    }

    /**
     * Buat backup baru. Mengembalikan info file yang dibuat.
     *
     * @param  string  $trigger  Penanda asal backup (manual, scheduled, pre-restore)
     * @return array{filename: string, path: string, size: int}
     */
    public function create(string $trigger = 'manual'): array
    {
        $driver = DB::connection()->getDriverName();
        $timestamp = now()->format('Ymd-His');

        $filename = match ($driver) {
            'pgsql' => "sipkud-{$trigger}-{$timestamp}.dump",
            'mysql', 'mariadb' => "sipkud-{$trigger}-{$timestamp}.sql.gz",
            default => throw new RuntimeException("Backup tidak mendukung driver database '{$driver}'."),
        };

        $path = $this->directory().'/'.$filename;

        match ($driver) {
            'pgsql' => $this->dumpPgsql($path),
            default => $this->dumpMysql($path),
        };

        if (! is_file($path) || filesize($path) === 0) {
            @unlink($path);
            throw new RuntimeException('Backup gagal: file hasil dump kosong.');
        }

        $this->audit('backup_created', "Backup database dibuat ({$trigger}): {$filename}");

        return [
            'filename' => $filename,
            'path' => $path,
            'size' => filesize($path),
        ];
    }

    /**
     * Daftar backup yang tersedia, terbaru lebih dulu.
     *
     * @return array<int, array{filename: string, size: int, created_at: Carbon}>
     */
    public function list(): array
    {
        $files = glob($this->directory().'/*') ?: [];

        $backups = [];
        foreach ($files as $file) {
            if (! is_file($file) || ! preg_match(self::FILENAME_PATTERN, basename($file))) {
                continue;
            }

            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'created_at' => Carbon::createFromTimestamp(filemtime($file), config('app.timezone')),
            ];
        }

        usort($backups, fn ($a, $b) => $b['created_at'] <=> $a['created_at']);

        return $backups;
    }

    /**
     * Path absolut sebuah file backup, dengan validasi nama (anti traversal).
     */
    public function path(string $filename): string
    {
        if (! preg_match(self::FILENAME_PATTERN, $filename)) {
            throw new RuntimeException('Nama file backup tidak valid.');
        }

        $path = $this->directory().'/'.$filename;

        if (! is_file($path)) {
            throw new RuntimeException("File backup tidak ditemukan: {$filename}");
        }

        return $path;
    }

    /**
     * Hapus sebuah file backup.
     */
    public function delete(string $filename): void
    {
        unlink($this->path($filename));

        $this->audit('backup_deleted', "File backup dihapus: {$filename}");
    }

    /**
     * Restore database dari file backup.
     *
     * Urutan: safety snapshot -> maintenance mode -> putuskan koneksi lain ->
     * restore -> migrate --force (terapkan migration yang lebih baru dari
     * backup) -> verifikasi integritas akuntansi -> maintenance off.
     *
     * @return array{safety_backup: string, integrity_ok: bool}
     */
    public function restore(string $filename): array
    {
        $path = $this->path($filename);
        $driver = DB::connection()->getDriverName();

        // Cocokkan format file dengan driver aktif
        $isPgDump = str_ends_with($filename, '.dump');
        if ($driver === 'pgsql' && ! $isPgDump) {
            throw new RuntimeException('Database aktif PostgreSQL - file restore harus berformat .dump (pg_dump custom).');
        }
        if (in_array($driver, ['mysql', 'mariadb']) && $isPgDump) {
            throw new RuntimeException('Database aktif MySQL - file restore harus berformat .sql.gz.');
        }

        // 1. Safety snapshot: kondisi saat ini selalu bisa dikembalikan
        $safety = $this->create('pre-restore');

        Artisan::call('down', ['--retry' => 30]);

        try {
            // 2. Putuskan koneksi lain agar drop/recreate tidak terblokir
            if ($driver === 'pgsql') {
                DB::statement(
                    'SELECT pg_terminate_backend(pid) FROM pg_stat_activity '
                    .'WHERE datname = current_database() AND pid <> pg_backend_pid()'
                );
            }
            DB::disconnect();

            // 3. Restore
            match ($driver) {
                'pgsql' => $this->restorePgsql($path),
                default => $this->restoreMysql($path),
            };

            DB::purge();

            // 4. Terapkan migration yang lebih baru daripada isi backup
            Artisan::call('migrate', ['--force' => true]);

            // 5. Verifikasi integritas akuntansi terhadap data hasil restore
            $integrityOk = Artisan::call('accounting:verify-integrity') === 0;

            $this->audit('backup_restored', sprintf(
                'Database di-restore dari %s (safety snapshot: %s, integritas: %s)',
                $filename,
                $safety['filename'],
                $integrityOk ? 'OK' : 'ADA KETIDAKSESUAIAN'
            ));

            return [
                'safety_backup' => $safety['filename'],
                'integrity_ok' => $integrityOk,
            ];
        } finally {
            Artisan::call('up');
        }
    }

    /**
     * Hapus backup lama, sisakan $keep terbaru. Mengembalikan jumlah terhapus.
     */
    public function prune(int $keep = 14): int
    {
        $backups = $this->list();
        $deleted = 0;

        foreach (array_slice($backups, $keep) as $backup) {
            unlink($this->directory().'/'.$backup['filename']);
            $deleted++;
        }

        return $deleted;
    }

    // -------------------------------------------------------------------
    // Driver: PostgreSQL
    // -------------------------------------------------------------------

    protected function dumpPgsql(string $path): void
    {
        $this->assertBinary('pg_dump');

        $config = DB::connection()->getConfig();

        $this->runProcess([
            'pg_dump',
            '--host', $config['host'],
            '--port', (string) ($config['port'] ?? 5432),
            '--username', $config['username'],
            '--dbname', $config['database'],
            '--format', 'custom',
            '--no-owner',
            '--file', $path,
        ], ['PGPASSWORD' => $config['password'] ?? '']);
    }

    protected function restorePgsql(string $path): void
    {
        $this->assertBinary('pg_restore');

        $config = DB::connection()->getConfig();

        $this->runProcess([
            'pg_restore',
            '--host', $config['host'],
            '--port', (string) ($config['port'] ?? 5432),
            '--username', $config['username'],
            '--dbname', $config['database'],
            '--clean',
            '--if-exists',
            '--no-owner',
            $path,
        ], ['PGPASSWORD' => $config['password'] ?? '']);
    }

    // -------------------------------------------------------------------
    // Driver: MySQL / MariaDB
    // -------------------------------------------------------------------

    protected function dumpMysql(string $path): void
    {
        $this->assertBinary('mysqldump');

        $config = DB::connection()->getConfig();

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --single-transaction --routines --triggers --no-tablespaces %s | gzip > %s',
            escapeshellarg($config['host']),
            escapeshellarg((string) ($config['port'] ?? 3306)),
            escapeshellarg($config['username']),
            escapeshellarg($config['database']),
            escapeshellarg($path)
        );

        $this->runShell($command, ['MYSQL_PWD' => $config['password'] ?? '']);
    }

    protected function restoreMysql(string $path): void
    {
        $this->assertBinary('mysql');

        $config = DB::connection()->getConfig();

        $command = sprintf(
            'gunzip -c %s | mysql --host=%s --port=%s --user=%s %s',
            escapeshellarg($path),
            escapeshellarg($config['host']),
            escapeshellarg((string) ($config['port'] ?? 3306)),
            escapeshellarg($config['username']),
            escapeshellarg($config['database'])
        );

        $this->runShell($command, ['MYSQL_PWD' => $config['password'] ?? '']);
    }

    // -------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------

    protected function runProcess(array $command, array $env = []): void
    {
        $process = new Process($command, null, $env, null, self::PROCESS_TIMEOUT);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('Proses backup/restore database gagal', [
                'command' => $command[0],
                'stderr' => $process->getErrorOutput(),
            ]);
            throw new RuntimeException(trim($process->getErrorOutput()) ?: 'Proses '.$command[0].' gagal.');
        }
    }

    protected function runShell(string $command, array $env = []): void
    {
        $process = Process::fromShellCommandline($command, null, $env, null, self::PROCESS_TIMEOUT);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('Proses backup/restore database gagal', [
                'stderr' => $process->getErrorOutput(),
            ]);
            throw new RuntimeException(trim($process->getErrorOutput()) ?: 'Proses shell gagal.');
        }
    }

    protected function assertBinary(string $binary): void
    {
        $check = new Process(['which', $binary]);
        $check->run();

        if (! $check->isSuccessful()) {
            throw new RuntimeException(
                "Perintah '{$binary}' tidak ditemukan di server. "
                .'Pastikan client tools database terpasang di image/host aplikasi.'
            );
        }
    }

    protected function audit(string $action, string $description): void
    {
        AuditLog::create([
            'model_type' => 'database',
            'model_id' => 0,
            'action' => $action,
            'user_id' => Auth::id(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'description' => $description,
        ]);

        Log::info($description, ['user_id' => Auth::id()]);
    }
}
