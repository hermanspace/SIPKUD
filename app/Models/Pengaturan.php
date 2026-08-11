<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Pengaturan
 *
 * Sistem pengaturan global untuk aplikasi SIPKUD
 * Hanya dapat diedit oleh Super Admin
 * Data digunakan untuk konfigurasi tampilan dan informasi instansi
 *
 * Catatan: Modul-modul berikut akan dikembangkan di fase selanjutnya:
 * - Pinjaman
 * - Kas
 * - Jurnal (Akuntansi)
 * - Aset
 * - Pelaporan
 */
class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $fillable = [
        'nama_instansi',
        'nama_daerah',
        'logo_instansi',
        'favicon',
        'alamat',
        'telepon',
        'warna_tema',
        'base_title',
        'persentase_shu',
    ];

    protected $casts = [
        'persentase_shu' => 'decimal:2',
    ];

    /**
     * Cache per-request: getSettings() dipanggil view composer '*' sehingga
     * tanpa cache setiap partial/komponen memicu query pengaturan sendiri
     * (ratusan query identik per halaman).
     */
    protected static ?self $cachedSettings = null;

    protected static function booted(): void
    {
        static::saved(function (): void {
            static::$cachedSettings = null;
        });
    }

    /**
     * Get singleton instance of pengaturan
     * Hanya ada satu record pengaturan dalam sistem
     */
    public static function getSettings(): self
    {
        return static::$cachedSettings ??= static::firstOrCreate(
            ['id' => 1],
            [
                'nama_instansi' => 'SIPKUD',
                'nama_daerah' => 'Kabupaten',
                'base_title' => 'SIPKUD - Sistem Informasi Pelaporan Keuangan USP Desa',
            ]
        );
    }
}
