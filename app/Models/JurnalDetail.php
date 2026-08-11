<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model JurnalDetail
 *
 * Detail jurnal (baris debit/kredit)
 * Setiap jurnal minimal memiliki 2 baris (1 debit, 1 kredit)
 */
class JurnalDetail extends Model
{
    protected $table = 'jurnal_detail';

    protected $fillable = [
        'jurnal_id',
        'akun_id',
        'posisi',
        'jumlah',
        'keterangan',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'posisi' => 'string',
    ];

    /**
     * Relasi ke jurnal
     */
    public function jurnal(): BelongsTo
    {
        return $this->belongsTo(Jurnal::class);
    }

    /**
     * Relasi ke akun
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class);
    }
}
