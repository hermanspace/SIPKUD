# Dokumentasi Periode Akuntansi

## Overview

Fitur **Manajemen Periode Akuntansi** adalah sistem untuk mengelola periode bulanan akuntansi BUM Desa, termasuk closing periode, opening balance, dan audit trail neraca saldo.

## Konsep Utama

### 1. Periode Akuntansi
- Format periode: `YYYY-MM` (contoh: `2026-01`)
- Setiap periode menyimpan neraca saldo (trial balance) bulanan
- Periode memiliki status: `open` atau `closed`

### 2. Status Periode
- **Open**: Periode masih aktif, transaksi dapat ditambah/diubah
- **Closed**: Periode sudah ditutup, tidak dapat diubah (audit trail)

### 3. Neraca Saldo Periode
Setiap periode menyimpan untuk setiap akun:
- **Saldo Awal** (Debit & Kredit): Dari saldo akhir periode sebelumnya
- **Mutasi** (Debit & Kredit): Transaksi periode berjalan
- **Saldo Akhir** (Debit & Kredit): Saldo awal + Mutasi

## Struktur Database

### Tabel: `neraca_saldo`

```sql
CREATE TABLE `neraca_saldo` (
  `id` BIGINT UNSIGNED PRIMARY KEY,
  `desa_id` BIGINT UNSIGNED NOT NULL,
  `unit_usaha_id` BIGINT UNSIGNED NULL,
  `akun_id` BIGINT UNSIGNED NOT NULL,
  `periode` VARCHAR(7) NOT NULL COMMENT 'Format: YYYY-MM',
  
  -- Saldo
  `saldo_awal_debit` DECIMAL(15,2) DEFAULT 0,
  `saldo_awal_kredit` DECIMAL(15,2) DEFAULT 0,
  `mutasi_debit` DECIMAL(15,2) DEFAULT 0,
  `mutasi_kredit` DECIMAL(15,2) DEFAULT 0,
  `saldo_akhir_debit` DECIMAL(15,2) DEFAULT 0,
  `saldo_akhir_kredit` DECIMAL(15,2) DEFAULT 0,
  
  -- Status Periode
  `status_periode` ENUM('open', 'closed') DEFAULT 'open',
  `closed_at` TIMESTAMP NULL,
  `closed_by` BIGINT UNSIGNED NULL,
  
  -- Audit
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  
  UNIQUE KEY `neraca_saldo_unique` (`desa_id`, `akun_id`, `periode`, `unit_usaha_id`)
);
```

## Fitur Utama

### 1. Manajemen Periode (`/periode`)

**Halaman Index**:
- Tampilkan daftar periode bulanan (12 bulan)
- Untuk setiap periode tampilkan:
  - Periode (bulan & tahun)
  - Jumlah akun yang memiliki saldo
  - Total Debit
  - Total Kredit
  - Status (Open/Closed)
  - Aksi (View Detail, Recalculate, Close/Reopen)

**Filter**:
- Desa (untuk Super Admin & Admin Kecamatan)
- Unit Usaha
- Tahun

**Aksi** (hanya untuk Admin Desa):
- **Recalculate**: Hitung ulang neraca saldo dari jurnal
- **Close Period**: Tutup periode dan buat opening balance periode berikutnya
- **Reopen Period**: Buka kembali periode yang sudah ditutup

### 2. Detail Periode (`/periode/{desa_id}/{periode}`)

**Halaman Show**:
- Tampilkan neraca saldo lengkap untuk periode tertentu
- Group by tipe akun (Aset, Kewajiban, Ekuitas, Pendapatan, Beban)
- Untuk setiap akun tampilkan:
  - Kode & Nama Akun
  - Saldo Awal (D & K)
  - Mutasi (D & K)
  - Saldo Akhir (D & K)
- Tampilkan total per kolom
- Balance check (Total Debit = Total Kredit)

**Filter**:
- Unit Usaha

## Service Layer: AccountingService

### Method: `postToLedger(Jurnal $jurnal)`
```php
/**
 * Posting jurnal ke ledger (neraca_saldo)
 * - Dipanggil otomatis saat jurnal di-post
 * - Update/create neraca_saldo untuk periode jurnal
 * - Hitung saldo akhir
 */
```

### Method: `recalculateBalance(int $desaId, string $periode, ?int $unitUsahaId = null)`
```php
/**
 * Recalculate neraca saldo untuk periode tertentu
 * - Hapus data neraca_saldo existing
 * - Re-post semua jurnal periode tersebut
 * - Hitung ulang saldo akhir
 */
```

### Method: `closePeriod(int $desaId, string $periode, ?int $unitUsahaId = null)`
```php
/**
 * Tutup periode akuntansi
 * - Validasi tidak ada jurnal draft
 * - Recalculate untuk memastikan data akurat
 * - Update status_periode = 'closed'
 * - Buat opening balance untuk periode berikutnya
 */
```

### Method: `reopenPeriod(int $desaId, string $periode, ?int $unitUsahaId = null)`
```php
/**
 * Buka kembali periode yang sudah ditutup
 * - Update status_periode = 'open'
 * - Hapus data closing (closed_at, closed_by)
 * - Untuk koreksi/adjustment
 */
```

## Hak Akses

### Super Admin & Admin Kecamatan
- **Read**: Lihat semua periode dari desa yang dapat diakses
- **Filter**: Dapat filter per desa
- **No Action**: Tidak dapat melakukan closing/recalculate

### Admin Desa
- **Read**: Lihat periode desa sendiri
- **Action**: Dapat melakukan Recalculate, Close, Reopen

## Alur Kerja (Workflow)

### A. Transaksi Harian
```
1. Input Kas Harian / Buku Memorial
   ↓
2. Jurnal dibuat otomatis (status: posted)
   ↓
3. AccountingService::postToLedger() dipanggil
   ↓
4. Neraca saldo untuk periode tersebut diupdate
```

### B. Closing Periode (Akhir Bulan)
```
1. Admin Desa klik "Close Period"
   ↓
2. Sistem validasi:
   - Tidak ada jurnal draft?
   ↓
3. Recalculate neraca saldo
   ↓
4. Update status_periode = 'closed'
   ↓
5. Buat opening balance periode berikutnya
   ↓
6. Periode tidak dapat diubah (audit trail)
```

### C. Koreksi Periode Tertutup
```
1. Admin Desa klik "Reopen Period"
   ↓
2. Status_periode = 'open'
   ↓
3. Dapat melakukan koreksi transaksi
   ↓
4. Setelah selesai, Close Period lagi
```

## File Struktur

### Migration
- `database/migrations/2025_12_26_100004_create_neraca_saldo_table.php`

### Model
- `app/Models/NeracaSaldo.php`

### Service
- `app/Services/AccountingService.php`
  - Metode: `postToLedger()`, `recalculateBalance()`, `closePeriod()`, `reopenPeriod()`

### Livewire Components
- `app/Livewire/Periode/Index.php`
- `app/Livewire/Periode/Show.php`

### Views
- `resources/views/livewire/periode/index.blade.php`
- `resources/views/livewire/periode/show.blade.php`

### Routes
```php
Route::get('periode', \App\Livewire\Periode\Index::class)->name('periode.index');
Route::get('periode/{desa_id}/{periode}', \App\Livewire\Periode\Show::class)->name('periode.show');
```

### Sidebar Menu
- Ditambahkan di semua role: Super Admin, Admin Kecamatan, Admin Desa
- Posisi: Setelah "Laporan", sebelum "Pengaturan"

## Best Practices

### 1. Prinsip Akuntansi
✅ **DO**:
- Selalu recalculate sebelum closing periode
- Validasi balance (debit = kredit) sebelum closing
- Pastikan tidak ada jurnal draft sebelum closing

❌ **DON'T**:
- Jangan ubah transaksi di periode yang sudah closed
- Jangan skip validasi balance
- Jangan hapus data neraca_saldo secara manual

### 2. Operasional
✅ **DO**:
- Close periode setiap akhir bulan
- Backup database sebelum closing periode penting
- Gunakan recalculate jika ada ketidaksesuaian data

❌ **DON'T**:
- Jangan close periode jika masih ada transaksi pending
- Jangan reopen periode tanpa alasan jelas
- Jangan close multiple periode sekaligus tanpa verifikasi

## Testing Checklist

### Unit Test
- [ ] `postToLedger()` menambah neraca_saldo dengan benar
- [ ] `recalculateBalance()` menghitung ulang dengan akurat
- [ ] `closePeriod()` membuat opening balance periode berikutnya
- [ ] `reopenPeriod()` mengembalikan status dengan benar

### Integration Test
- [ ] Kas Harian → Jurnal → Neraca Saldo
- [ ] Buku Memorial → Jurnal → Neraca Saldo
- [ ] Close Period → Opening Balance
- [ ] Filter desa & unit usaha berfungsi

### Manual Test
- [ ] Akses halaman periode sebagai Super Admin
- [ ] Akses halaman periode sebagai Admin Kecamatan
- [ ] Akses halaman periode sebagai Admin Desa
- [ ] Recalculate periode
- [ ] Close periode
- [ ] Reopen periode
- [ ] Lihat detail periode
- [ ] Filter per desa
- [ ] Filter per unit usaha
- [ ] Balance check

## Troubleshooting

### Q: Total Debit ≠ Total Kredit
**A**: Jalankan `recalculateBalance()` untuk periode tersebut.

### Q: Periode tidak bisa ditutup
**A**: Pastikan tidak ada jurnal dengan status draft di periode tersebut.

### Q: Saldo Awal tidak sesuai
**A**: 
1. Pastikan periode sebelumnya sudah ditutup dengan benar
2. Jalankan `recalculateBalance()` untuk periode sebelumnya
3. Jalankan `closePeriod()` periode sebelumnya
4. Saldo awal akan otomatis terisi dari saldo akhir periode sebelumnya

### Q: Data neraca saldo kosong
**A**:
1. Pastikan ada transaksi yang sudah di-post di periode tersebut
2. Jalankan `recalculateBalance()` untuk periode tersebut

## Changelog

### v1.0.0 (2026-01-23)
- ✨ Fitur manajemen periode akuntansi
- ✨ Closing & reopening periode
- ✨ Opening balance otomatis
- ✨ Recalculate neraca saldo
- ✨ Detail neraca saldo per periode
- ✨ Filter per desa & unit usaha
- ✨ Hak akses berbasis role

## Author
System Analyst & Senior Software Engineer
BUM Desa Financial System
