<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Desa
 *
 * Master data untuk desa (village)
 * Merupakan basis multi-tenancy sistem SIPKUD
 * Setiap desa memiliki data terpisah (kelompok, anggota, akun, pinjaman, dll)
 *
 * Catatan: Modul-modul berikut akan dikembangkan di fase selanjutnya:
 * - Kas
 * - Jurnal (Akuntansi)
 * - Aset
 * - Pelaporan
 */
class Desa extends Model
{
    use HasFactory;

    protected $table = 'desa';

    protected $fillable = [
        'kecamatan_id',
        'nama_desa',
        'kode_desa',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    /**
     * Relasi ke kecamatan
     */
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    /**
     * Relasi ke kelompok
     */
    public function kelompok(): HasMany
    {
        return $this->hasMany(Kelompok::class);
    }

    /**
     * Relasi ke anggota
     */
    public function anggota(): HasMany
    {
        return $this->hasMany(Anggota::class);
    }

    /**
     * Relasi ke akun
     */
    public function akun(): HasMany
    {
        return $this->hasMany(Akun::class);
    }

    /**
     * Relasi ke users (Admin Desa dan Executive View)
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Relasi ke pinjaman
     */
    public function pinjaman(): HasMany
    {
        return $this->hasMany(Pinjaman::class);
    }

    /**
     * Relasi ke unit usaha
     */
    public function unitUsaha(): HasMany
    {
        return $this->hasMany(UnitUsaha::class);
    }

    /**
     * Relasi ke jurnal
     */
    public function jurnal(): HasMany
    {
        return $this->hasMany(Jurnal::class);
    }

    /**
     * Scope untuk filter aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }
}
