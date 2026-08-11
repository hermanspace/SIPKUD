<?php

namespace App\Models;

use App\Models\Concerns\HasDesaScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model AsetTetap
 *
 * Registrasi aset tetap dengan penyusutan garis lurus bulanan.
 * Jurnal penyusutan dibuat otomatis oleh AsetTetapService.
 */
class AsetTetap extends Model
{
    use HasDesaScope, HasFactory, SoftDeletes;

    protected $table = 'aset_tetap';

    protected $fillable = [
        'desa_id',
        'unit_usaha_id',
        'nama_aset',
        'tanggal_perolehan',
        'harga_perolehan',
        'nilai_residu',
        'umur_bulan',
        'akun_aset_id',
        'akun_akumulasi_id',
        'akun_beban_id',
        'akumulasi_tercatat',
        'periode_penyusutan_terakhir',
        'status',
        'created_by',
    ];

    protected $casts = [
        'tanggal_perolehan' => 'date',
        'harga_perolehan' => 'decimal:2',
        'nilai_residu' => 'decimal:2',
        'akumulasi_tercatat' => 'decimal:2',
        'umur_bulan' => 'integer',
    ];

    /**
     * Beban penyusutan per bulan (garis lurus).
     */
    public function getPenyusutanBulananAttribute(): float
    {
        if ($this->umur_bulan < 1) {
            return 0.0;
        }

        return round(((float) $this->harga_perolehan - (float) $this->nilai_residu) / $this->umur_bulan, 2);
    }

    /**
     * Nilai buku saat ini.
     */
    public function getNilaiBukuAttribute(): float
    {
        return (float) $this->harga_perolehan - (float) $this->akumulasi_tercatat;
    }

    /**
     * Sisa nilai yang masih bisa disusutkan.
     */
    public function getSisaDisusutkanAttribute(): float
    {
        return max(0, (float) $this->harga_perolehan - (float) $this->nilai_residu - (float) $this->akumulasi_tercatat);
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    public function akunAset(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'akun_aset_id');
    }

    public function akunAkumulasi(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'akun_akumulasi_id');
    }

    public function akunBeban(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'akun_beban_id');
    }
}
