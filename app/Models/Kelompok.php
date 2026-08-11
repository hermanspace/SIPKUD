<?php

namespace App\Models;

use App\Models\Concerns\HasDesaScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Kelompok
 *
 * Master data untuk kelompok (group)
 * Digunakan untuk mengkategorikan anggota
 * Akan digunakan oleh modul Pinjaman di fase selanjutnya
 *
 * Catatan: Modul-modul berikut akan dikembangkan di fase selanjutnya:
 * - Pinjaman
 * - Kas
 * - Jurnal (Akuntansi)
 * - Aset
 * - Pelaporan
 */
class Kelompok extends Model
{
    use HasDesaScope, SoftDeletes;

    protected $table = 'kelompok';

    protected $fillable = [
        'desa_id',
        'nama_kelompok',
        'keterangan',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    /**
     * Relasi ke desa
     */
    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    /**
     * Relasi ke anggota
     */
    public function anggota(): HasMany
    {
        return $this->hasMany(Anggota::class);
    }

    /**
     * Relasi ke user yang membuat
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke user yang terakhir mengupdate
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope untuk filter aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }
}
