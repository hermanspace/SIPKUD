<?php

namespace App\Models;

use App\Models\Concerns\HasDesaScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransaksiKas extends Model
{
    use HasDesaScope;

    protected $table = 'transaksi_kas';

    protected $fillable = [
        'desa_id',
        'unit_usaha_id',
        'tanggal_transaksi',
        'uraian',
        'jenis_transaksi',
        'akun_kas_id',
        'akun_lawan_id',
        'jumlah',
        'pinjaman_id',
        'angsuran_pinjaman_id',
        'deleted_by',
        'deleted_reason',
    ];

    protected $casts = [
        'tanggal_transaksi' => 'date',
        'jumlah' => 'decimal:2',
    ];

    /**
     * Relasi ke Desa
     */
    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    /**
     * Relasi ke Pinjaman (untuk kas keluar)
     */
    public function pinjaman(): BelongsTo
    {
        return $this->belongsTo(Pinjaman::class);
    }

    /**
     * Relasi ke Angsuran Pinjaman (untuk kas masuk)
     */
    public function angsuranPinjaman(): BelongsTo
    {
        return $this->belongsTo(AngsuranPinjaman::class);
    }

    /**
     * Relasi ke Unit Usaha
     */
    public function unitUsaha(): BelongsTo
    {
        return $this->belongsTo(UnitUsaha::class);
    }

    /**
     * Relasi ke Akun Kas
     */
    public function akunKas(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'akun_kas_id');
    }

    /**
     * Relasi ke Akun Lawan
     */
    public function akunLawan(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'akun_lawan_id');
    }

    /**
     * Relasi ke Jurnal (one to one)
     */
    public function jurnal(): HasOne
    {
        return $this->hasOne(Jurnal::class);
    }
}
