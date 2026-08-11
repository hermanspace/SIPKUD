<?php

namespace App\Models;

use App\Models\Concerns\HasDesaScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model UnitUsaha
 *
 * Representasi unit usaha dalam BUM Desa
 * Contoh: USP (Unit Simpan Pinjam), UED-SP, Unit Perdagangan, dll
 * Setiap unit usaha memiliki laporan keuangan terpisah
 */
class UnitUsaha extends Model
{
    use HasDesaScope, HasFactory, SoftDeletes;

    protected $table = 'unit_usaha';

    protected $fillable = [
        'desa_id',
        'kode_unit',
        'nama_unit',
        'deskripsi',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Relasi ke desa
     */
    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    /**
     * Relasi ke jurnal
     */
    public function jurnal(): HasMany
    {
        return $this->hasMany(Jurnal::class);
    }

    /**
     * Relasi ke user pembuat
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke user yang update
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
