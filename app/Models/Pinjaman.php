<?php

namespace App\Models;

use App\Models\Concerns\HasDesaScope;
use App\Services\AccountingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

/**
 * Model Pinjaman
 *
 * Transaksi pinjaman anggota USP/UED-SP
 * Merupakan transaksi awal sistem simpan pinjam
 *
 * Catatan:
 * - Saldo pinjaman TIDAK disimpan di database
 * - Semua saldo dihitung dari transaksi
 * - LPP UED adalah LAPORAN, bukan input
 */
class Pinjaman extends Model
{
    use HasDesaScope;

    protected $table = 'pinjaman';

    protected $fillable = [
        'desa_id',
        'anggota_id',
        'sektor_usaha_id',
        'nomor_pinjaman',
        'tanggal_pinjaman',
        'jumlah_pinjaman',
        'jangka_waktu_bulan',
        'jasa_persen',
        'status_pinjaman',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pinjaman' => 'date',
            'jumlah_pinjaman' => 'decimal:2',
            'jangka_waktu_bulan' => 'integer',
            'jasa_persen' => 'decimal:2',
            'status_pinjaman' => 'string',
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
    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    /**
     * Relasi ke sektor usaha
     */
    public function sektorUsaha(): BelongsTo
    {
        return $this->belongsTo(SektorUsaha::class);
    }

    /**
     * Relasi ke angsuran
     */
    public function angsuran(): HasMany
    {
        return $this->hasMany(AngsuranPinjaman::class);
    }

    /**
     * Boot method untuk event model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Otomatis buat transaksi kas keluar dan jurnal saat pinjaman dibuat
        static::created(function (Pinjaman $pinjaman) {
            $pinjaman->load('anggota');

            // Get akun
            $akunKas = Akun::aktif()
                ->where('nama_akun', 'Kas')
                ->first();

            $akunPiutang = Akun::aktif()
                ->where('nama_akun', 'Piutang Pinjaman Anggota')
                ->first();

            if (! $akunKas || ! $akunPiutang) {
                Log::warning("Akun tidak ditemukan untuk pinjaman {$pinjaman->id}");

                return;
            }

            // Get unit usaha USP
            $unitUsaha = UnitUsaha::where('desa_id', $pinjaman->desa_id)
                ->where('kode_unit', 'USP')
                ->first();

            // Create TransaksiKas
            $transaksiKas = TransaksiKas::create([
                'desa_id' => $pinjaman->desa_id,
                'unit_usaha_id' => $unitUsaha?->id,
                'tanggal_transaksi' => $pinjaman->tanggal_pinjaman,
                'uraian' => "Pencairan Pinjaman - {$pinjaman->nomor_pinjaman} - {$pinjaman->anggota->nama}",
                'jenis_transaksi' => 'keluar',
                'akun_kas_id' => $akunKas->id,
                'akun_lawan_id' => $akunPiutang->id,
                'jumlah' => $pinjaman->jumlah_pinjaman,
                'pinjaman_id' => $pinjaman->id,
            ]);

            // Auto-create Jurnal
            $accountingService = app(AccountingService::class);
            $accountingService->createJurnal([
                'desa_id' => $pinjaman->desa_id,
                'unit_usaha_id' => $unitUsaha?->id,
                'tanggal_transaksi' => $pinjaman->tanggal_pinjaman,
                'jenis_jurnal' => 'kas_harian',
                'keterangan' => "Pencairan Pinjaman - {$pinjaman->nomor_pinjaman} - {$pinjaman->anggota->nama}",
                'status' => 'posted',
                'transaksi_kas_id' => $transaksiKas->id,
                'pinjaman_id' => $pinjaman->id,
                'details' => [
                    [
                        'akun_id' => $akunPiutang->id,
                        'posisi' => 'debit',
                        'jumlah' => $pinjaman->jumlah_pinjaman,
                        'keterangan' => 'Piutang pinjaman anggota',
                    ],
                    [
                        'akun_id' => $akunKas->id,
                        'posisi' => 'kredit',
                        'jumlah' => $pinjaman->jumlah_pinjaman,
                        'keterangan' => 'Kas keluar',
                    ],
                ],
            ]);
        });
    }

    /**
     * Hitung total pokok yang sudah dibayar
     * Dihitung dari transaksi angsuran, bukan dari database
     */
    public function getTotalPokokDibayarAttribute(): float
    {
        return (float) $this->angsuran()->sum('pokok_dibayar');
    }

    /**
     * Hitung total jasa yang sudah dibayar
     * Dihitung dari transaksi angsuran, bukan dari database
     */
    public function getTotalJasaDibayarAttribute(): float
    {
        return (float) $this->angsuran()->sum('jasa_dibayar');
    }

    /**
     * Hitung sisa pinjaman
     * Sisa = jumlah_pinjaman - total pokok yang sudah dibayar
     */
    public function getSisaPinjamanAttribute(): float
    {
        $totalPokokDibayar = (float) $this->angsuran()->sum('pokok_dibayar');

        return max(0, (float) $this->jumlah_pinjaman - $totalPokokDibayar);
    }

    /**
     * Hitung status pinjaman berdasarkan sisa pinjaman
     * AKTIF jika sisa_pinjaman > 0
     * LUNAS jika sisa_pinjaman = 0
     * Dihitung dari transaksi, bukan dari database
     */
    public function getStatusPinjamanCalculatedAttribute(): string
    {
        $sisaPinjaman = $this->sisa_pinjaman;

        return $sisaPinjaman > 0 ? 'aktif' : 'lunas';
    }

    /**
     * Update status pinjaman berdasarkan sisa pinjaman
     * Dipanggil otomatis ketika angsuran dibuat atau dihapus
     */
    public function updateStatusFromSisa(): void
    {
        $newStatus = $this->status_pinjaman_calculated;

        // Hanya update jika status berbeda
        if ($this->status_pinjaman !== $newStatus) {
            $this->update(['status_pinjaman' => $newStatus]);
        }
    }

    /**
     * Scope untuk filter aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('status_pinjaman', 'aktif');
    }

    /**
     * Scope untuk filter lunas
     */
    public function scopeLunas($query)
    {
        return $query->where('status_pinjaman', 'lunas');
    }

    /**
     * Tunggakan angsuran (dalam bulan): selisih angsuran yang seharusnya
     * sudah dibayar (berdasar umur pinjaman & tenor) dengan yang terbayar.
     */
    public function getTunggakanBulanAttribute(): int
    {
        if ($this->status_pinjaman !== 'aktif') {
            return 0;
        }

        $bulanBerjalan = (int) $this->tanggal_pinjaman->diffInMonths(now());
        $angsuranDiharapkan = min($bulanBerjalan, (int) $this->jangka_waktu_bulan);
        $angsuranDibayar = $this->angsuran()->count();

        return max(0, $angsuranDiharapkan - $angsuranDibayar);
    }

    /**
     * Kolektibilitas pinjaman (konvensi penilaian kesehatan KSP/USP):
     * lancar, kurang_lancar, diragukan, macet - batas di config/accounting.php.
     */
    public function getKolektibilitasAttribute(): string
    {
        $tunggakan = $this->tunggakan_bulan;
        $batas = config('accounting.kolektibilitas');

        return match (true) {
            $tunggakan >= $batas['macet'] => 'macet',
            $tunggakan >= $batas['diragukan'] => 'diragukan',
            $tunggakan >= $batas['kurang_lancar'] => 'kurang_lancar',
            default => 'lancar',
        };
    }
}
