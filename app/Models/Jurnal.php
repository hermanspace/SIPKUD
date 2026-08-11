<?php

namespace App\Models;

use App\Models\Concerns\HasDesaScope;
use App\Services\AccountingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Jurnal
 *
 * Header jurnal untuk mencatat transaksi akuntansi
 * Prinsip: Debit = Kredit (double entry)
 * Sumber: Kas Harian atau Buku Memorial
 */
class Jurnal extends Model
{
    use HasDesaScope, SoftDeletes;

    protected $table = 'jurnal';

    protected $fillable = [
        'desa_id',
        'unit_usaha_id',
        'nomor_jurnal',
        'tanggal_transaksi',
        'jenis_jurnal',
        'keterangan',
        'total_debit',
        'total_kredit',
        'status',
        'transaksi_kas_id',
        'pinjaman_id',
        'angsuran_pinjaman_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_reason',
    ];

    protected $casts = [
        'tanggal_transaksi' => 'date',
        'total_debit' => 'decimal:2',
        'total_kredit' => 'decimal:2',
        'jenis_jurnal' => 'string',
        'status' => 'string',
    ];

    /**
     * Boot method untuk auto-generate nomor jurnal dan auto-post ke ledger
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($jurnal) {
            if (! $jurnal->nomor_jurnal) {
                $jurnal->nomor_jurnal = static::generateNomorJurnal($jurnal->desa_id);
            }
        });

        // Auto-post ke ledger saat status menjadi 'posted'
        static::updated(function ($jurnal) {
            if ($jurnal->isDirty('status') && $jurnal->status === 'posted' && $jurnal->wasChanged('status')) {
                try {
                    app(AccountingService::class)->postToLedger($jurnal);
                } catch (\Exception $e) {
                    // Log error but don't fail the update
                    \Log::error('Failed to auto-post jurnal to ledger: '.$e->getMessage(), [
                        'jurnal_id' => $jurnal->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }

    /**
     * Generate nomor jurnal otomatis
     * Format: JRN/YYYY/MM/XXXXX
     */
    public static function generateNomorJurnal($desaId): string
    {
        $tahun = now()->format('Y');
        $bulan = now()->format('m');

        // Tanpa global scope & termasuk soft-deleted: penomoran harus konsisten
        // untuk desa yang sama siapa pun user yang membuat jurnal
        $lastJurnal = static::withoutGlobalScopes()
            ->withTrashed()
            ->where('desa_id', $desaId)
            ->whereYear('created_at', $tahun)
            ->whereMonth('created_at', $bulan)
            ->orderBy('id', 'desc')
            ->first();

        $urutan = $lastJurnal ? (int) substr($lastJurnal->nomor_jurnal, -5) + 1 : 1;

        return sprintf('JRN/%s/%s/%05d', $tahun, $bulan, $urutan);
    }

    /**
     * Relasi ke desa
     */
    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    /**
     * Relasi ke unit usaha
     */
    public function unitUsaha(): BelongsTo
    {
        return $this->belongsTo(UnitUsaha::class);
    }

    /**
     * Relasi ke detail jurnal
     */
    public function details(): HasMany
    {
        return $this->hasMany(JurnalDetail::class);
    }

    /**
     * Relasi ke transaksi kas
     */
    public function transaksiKas(): BelongsTo
    {
        return $this->belongsTo(TransaksiKas::class);
    }

    /**
     * Relasi ke pinjaman
     */
    public function pinjaman(): BelongsTo
    {
        return $this->belongsTo(Pinjaman::class);
    }

    /**
     * Relasi ke angsuran pinjaman
     */
    public function angsuranPinjaman(): BelongsTo
    {
        return $this->belongsTo(AngsuranPinjaman::class);
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
     * Scope untuk filter posted
     */
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    /**
     * Scope untuk filter berdasarkan jenis jurnal
     */
    public function scopeByJenis($query, $jenis)
    {
        return $query->where('jenis_jurnal', $jenis);
    }

    /**
     * Scope untuk filter berdasarkan periode
     */
    public function scopePeriode($query, $bulan, $tahun)
    {
        return $query->whereMonth('tanggal_transaksi', $bulan)
            ->whereYear('tanggal_transaksi', $tahun);
    }

    /**
     * Check apakah jurnal balanced (debit = kredit)
     */
    public function isBalanced(): bool
    {
        return bccomp($this->total_debit, $this->total_kredit, 2) === 0;
    }
}
