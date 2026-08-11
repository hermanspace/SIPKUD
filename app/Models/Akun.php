<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Akun
 *
 * Master data Chart of Accounts (COA) - GLOBAL untuk seluruh desa.
 * Hanya Admin dan Super Admin yang dapat menambah/mengedit.
 * Admin Desa hanya dapat menggunakan akun yang sudah ada.
 */
class Akun extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'akun';

    protected $fillable = [
        'kode_akun',
        'nama_akun',
        'tipe_akun',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tipe_akun' => 'string',
            'status' => 'string',
        ];
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

    /**
     * Scope untuk filter berdasarkan tipe akun
     */
    public function scopeByTipe($query, $tipe)
    {
        return $query->where('tipe_akun', $tipe);
    }
}
