<?php

namespace App\Models;

use App\Services\AccountingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

/**
 * Model AngsuranPinjaman
 *
 * Transaksi pembayaran angsuran pinjaman
 * Mencatat setiap pembayaran angsuran (pokok, jasa, denda)
 *
 * Catatan:
 * - Saldo pinjaman TIDAK disimpan di database
 * - Semua saldo dihitung dari transaksi
 * - Total dibayar = pokok_dibayar + jasa_dibayar + denda_dibayar
 * - Status pinjaman diupdate otomatis ketika angsuran dibuat atau dihapus
 */
class AngsuranPinjaman extends Model
{
    protected $table = 'angsuran_pinjaman';

    protected $fillable = [
        'pinjaman_id',
        'tanggal_bayar',
        'angsuran_ke',
        'pokok_dibayar',
        'jasa_dibayar',
        'denda_dibayar',
        'total_dibayar',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_bayar' => 'date',
            'angsuran_ke' => 'integer',
            'pokok_dibayar' => 'decimal:2',
            'jasa_dibayar' => 'decimal:2',
            'denda_dibayar' => 'decimal:2',
            'total_dibayar' => 'decimal:2',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-update status pinjaman dan buat transaksi kas masuk + jurnal ketika angsuran dibuat
        static::created(function (AngsuranPinjaman $angsuran) {
            $angsuran->load('pinjaman.anggota');
            $pinjaman = $angsuran->pinjaman;

            // Update status pinjaman
            $pinjaman->updateStatusFromSisa();

            // Get akun
            $akunKas = Akun::aktif()
                ->where('nama_akun', 'Kas')
                ->first();

            $akunPiutang = Akun::aktif()
                ->where('nama_akun', 'Piutang Pinjaman Anggota')
                ->first();

            $akunPendapatanJasa = Akun::aktif()
                ->where('nama_akun', 'like', '%Pendapatan Jasa Pinjaman%')
                ->orWhere('nama_akun', 'like', '%Pendapatan Jasa%')
                ->first();

            $akunPendapatanDenda = Akun::aktif()
                ->where('nama_akun', 'like', '%Denda%')
                ->first();

            if (! $akunKas || ! $akunPiutang) {
                Log::warning("Akun tidak ditemukan untuk angsuran {$angsuran->id}");

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
                'tanggal_transaksi' => $angsuran->tanggal_bayar,
                'uraian' => "Pembayaran Angsuran ke-{$angsuran->angsuran_ke} - {$pinjaman->nomor_pinjaman} - {$pinjaman->anggota->nama}",
                'jenis_transaksi' => 'masuk',
                'akun_kas_id' => $akunKas->id,
                'akun_lawan_id' => $akunPiutang->id, // Default, akan di-override di jurnal
                'jumlah' => $angsuran->total_dibayar,
                'angsuran_pinjaman_id' => $angsuran->id,
            ]);

            // Auto-create Jurnal (multi-account)
            $accountingService = app(AccountingService::class);
            $details = [
                [
                    'akun_id' => $akunKas->id,
                    'posisi' => 'debit',
                    'jumlah' => $angsuran->total_dibayar,
                    'keterangan' => 'Kas masuk',
                ],
            ];

            // Kredit: Piutang (pokok)
            if ($angsuran->pokok_dibayar > 0) {
                $details[] = [
                    'akun_id' => $akunPiutang->id,
                    'posisi' => 'kredit',
                    'jumlah' => $angsuran->pokok_dibayar,
                    'keterangan' => 'Pelunasan pokok pinjaman',
                ];
            }

            // Kredit: Pendapatan Jasa (jasa)
            if ($angsuran->jasa_dibayar > 0 && $akunPendapatanJasa) {
                $details[] = [
                    'akun_id' => $akunPendapatanJasa->id,
                    'posisi' => 'kredit',
                    'jumlah' => $angsuran->jasa_dibayar,
                    'keterangan' => 'Pendapatan jasa pinjaman',
                ];
            }

            // Kredit: Pendapatan Denda (denda, jika ada)
            if ($angsuran->denda_dibayar > 0 && $akunPendapatanDenda) {
                $details[] = [
                    'akun_id' => $akunPendapatanDenda->id,
                    'posisi' => 'kredit',
                    'jumlah' => $angsuran->denda_dibayar,
                    'keterangan' => 'Pendapatan denda keterlambatan',
                ];
            }

            $accountingService->createJurnal([
                'desa_id' => $pinjaman->desa_id,
                'unit_usaha_id' => $unitUsaha?->id,
                'tanggal_transaksi' => $angsuran->tanggal_bayar,
                'jenis_jurnal' => 'kas_harian',
                'keterangan' => "Pembayaran Angsuran ke-{$angsuran->angsuran_ke} - {$pinjaman->nomor_pinjaman} - {$pinjaman->anggota->nama}",
                'status' => 'posted',
                'transaksi_kas_id' => $transaksiKas->id,
                'angsuran_pinjaman_id' => $angsuran->id,
                'details' => $details,
            ]);
        });

        // Auto-update status pinjaman ketika angsuran dihapus
        // Menggunakan deleting untuk menyimpan pinjaman_id sebelum dihapus
        static::deleting(function ($angsuran) {
            // Simpan pinjaman_id sebelum model dihapus
            $pinjamanId = $angsuran->pinjaman_id;

            // Setelah model dihapus, update status pinjaman
            static::deleted(function () use ($pinjamanId) {
                $pinjaman = Pinjaman::find($pinjamanId);
                if ($pinjaman) {
                    $pinjaman->updateStatusFromSisa();
                }
            });
        });
    }

    /**
     * Relasi ke pinjaman
     */
    public function pinjaman(): BelongsTo
    {
        return $this->belongsTo(Pinjaman::class);
    }
}
