<?php

namespace App\Models;

use App\Models\Concerns\HasDesaScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model NeracaSaldo
 *
 * Menyimpan saldo per akun per periode (YYYY-MM)
 * Mendukung closing periode untuk audit trail
 *
 * Note: Tidak menggunakan SoftDeletes karena data ini adalah audit trail
 * yang tidak boleh dihapus
 */
class NeracaSaldo extends Model
{
    use HasDesaScope, HasFactory;

    protected $table = 'neraca_saldo';

    protected $fillable = [
        'desa_id',
        'unit_usaha_id',
        'akun_id',
        'periode',
        'saldo_awal_debit',
        'saldo_awal_kredit',
        'mutasi_debit',
        'mutasi_kredit',
        'saldo_akhir_debit',
        'saldo_akhir_kredit',
        'status_periode',
        'closed_at',
        'closed_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'saldo_awal_debit' => 'decimal:2',
        'saldo_awal_kredit' => 'decimal:2',
        'mutasi_debit' => 'decimal:2',
        'mutasi_kredit' => 'decimal:2',
        'saldo_akhir_debit' => 'decimal:2',
        'saldo_akhir_kredit' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    /**
     * Relasi ke Desa
     */
    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    /**
     * Relasi ke Unit Usaha
     */
    public function unitUsaha(): BelongsTo
    {
        return $this->belongsTo(UnitUsaha::class);
    }

    /**
     * Relasi ke Akun
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class);
    }

    /**
     * Relasi ke User (creator)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke User (updater)
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relasi ke User (closer)
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Scope untuk filter berdasarkan periode
     */
    public function scopeByPeriode($query, string $periode)
    {
        return $query->where('periode', $periode);
    }

    /**
     * Scope untuk periode yang masih open
     */
    public function scopeOpen($query)
    {
        return $query->where('status_periode', 'open');
    }

    /**
     * Scope untuk periode yang sudah closed
     */
    public function scopeClosed($query)
    {
        return $query->where('status_periode', 'closed');
    }

    /**
     * Hitung saldo akhir nett
     */
    public function getSaldoAkhirNettAttribute(): float
    {
        return (float) ($this->saldo_akhir_debit - $this->saldo_akhir_kredit);
    }

    /**
     * Check apakah periode ini sudah closed
     */
    public function isClosed(): bool
    {
        return $this->status_periode === 'closed';
    }

    /**
     * Check apakah periode ini masih open
     */
    public function isOpen(): bool
    {
        return $this->status_periode === 'open';
    }
}
