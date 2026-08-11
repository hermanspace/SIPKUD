<?php

namespace App\Models;

use App\Models\Concerns\HasDesaScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Anggota
 *
 * Master data untuk anggota (member)
 * Digunakan untuk menyimpan data anggota USP/UED-SP
 *
 * Catatan: Modul-modul berikut akan dikembangkan di fase selanjutnya:
 * - Kas
 * - Jurnal (Akuntansi)
 * - Aset
 * - Pelaporan
 */
class Anggota extends Model
{
    use HasDesaScope, SoftDeletes;

    protected $table = 'anggota';

    protected $fillable = [
        'desa_id',
        'kelompok_id',
        'nama',
        'nik',
        'alamat',
        'nomor_hp',
        'jenis_kelamin',
        'tanggal_gabung',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_gabung' => 'date',
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
     * Relasi ke kelompok
     */
    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
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
     * Relasi ke pinjaman
     */
    public function pinjaman(): HasMany
    {
        return $this->hasMany(Pinjaman::class);
    }

    /**
     * Scope untuk filter aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Scope untuk filter berdasarkan kelompok
     */
    public function scopeByKelompok($query, $kelompokId)
    {
        return $query->where('kelompok_id', $kelompokId);
    }
}
