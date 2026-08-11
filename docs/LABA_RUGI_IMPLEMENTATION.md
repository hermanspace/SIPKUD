# ✅ Implementasi Laporan Laba Rugi - Format Lengkap

## 📊 **STATUS: SUDAH DIIMPLEMENTASIKAN**

---

## 🎯 **REQUIREMENT YANG DIPENUHI**

| Requirement | Status | Implementasi |
|------------|--------|--------------|
| ✅ **Berdasarkan tabel neraca_saldo** | **DONE** | Query dari `neraca_saldo` table |
| ✅ **Laba Rugi Bulanan (mutasi)** | **DONE** | Mode 'bulanan' menggunakan mutasi |
| ✅ **Laba Rugi Kumulatif (saldo akhir)** | **DONE** | Mode 'kumulatif' menggunakan saldo akhir |
| ✅ **Hitung Laba Bersih** | **DONE** | Total Pendapatan - Total Biaya |
| ✅ **Output Service** | **DONE** | `getLabaRugiFromLedger()` |
| ✅ **Query** | **DONE** | LEFT JOIN akun dengan neraca_saldo |
| ✅ **Struktur data hasil** | **DONE** | Format lengkap dengan detail |

---

## 📁 **FILE YANG DIMODIFIKASI**

### 1. **AccountingService.php**
**Method Baru:** `getLabaRugiFromLedger()`

```php
/**
 * Get Laba Rugi dari tabel neraca_saldo (ledger)
 * Support 2 mode: Bulanan (mutasi) dan Kumulatif (saldo akhir)
 * 
 * @param int $desaId
 * @param string $periode Format: YYYY-MM (contoh: 2026-01)
 * @param string $mode 'bulanan' atau 'kumulatif'
 * @param int|null $unitUsahaId
 * @return array
 */
public function getLabaRugiFromLedger(
    int $desaId, 
    string $periode, 
    string $mode = 'bulanan',
    ?int $unitUsahaId = null
): array
```

**Query Logic:**
- ✅ LEFT JOIN `akun` dengan `neraca_saldo`
- ✅ Filter: `tipe_akun IN ('pendapatan', 'beban')`
- ✅ Filter by `desa_id`, `periode`, `unit_usaha_id`
- ✅ Mode Bulanan: gunakan `mutasi_debit/kredit`
- ✅ Mode Kumulatif: gunakan `saldo_akhir_debit/kredit`

**Format Output:**
```php
[
    'mode' => 'bulanan' | 'kumulatif',
    'periode' => '2026-01',
    'pendapatan' => 5000000.00,
    'beban' => 3000000.00,
    'laba_bersih' => 2000000.00,
    'detail_pendapatan' => [...],
    'detail_beban' => [...],
]
```

### 2. **Livewire/Laporan/LabaRugi.php**
**Updated:**
- ✅ Property `$mode` untuk memilih mode (bulanan/kumulatif)
- ✅ Method `render()` menggunakan `getLabaRugiFromLedger()`
- ✅ Method `exportPdf()` menggunakan `getLabaRugiFromLedger()`
- ✅ Convert `bulan` + `tahun` → `periode` (YYYY-MM)

### 3. **resources/views/livewire/laporan/laba-rugi.blade.php**
**Updated:**
- ✅ Mode selector dropdown
- ✅ Info box untuk menjelaskan mode yang dipilih
- ✅ Display menggunakan `jumlah` dari detail
- ✅ Variable `$labaBersih` (bukan `$labaRugi`)

---

## 📋 **QUERY YANG DIGUNAKAN**

### **Query SQL (Mode Bulanan):**
```sql
SELECT 
    a.id as akun_id,
    a.kode_akun,
    a.nama_akun,
    a.tipe_akun,
    COALESCE(ns.mutasi_debit, 0) as mutasi_debit,
    COALESCE(ns.mutasi_kredit, 0) as mutasi_kredit,
    COALESCE(ns.saldo_akhir_debit, 0) as saldo_akhir_debit,
    COALESCE(ns.saldo_akhir_kredit, 0) as saldo_akhir_kredit
FROM akun a
LEFT JOIN neraca_saldo ns ON (
    ns.akun_id = a.id 
    AND ns.desa_id = ? 
    AND ns.periode = ?
    AND (ns.unit_usaha_id = ? OR (ns.unit_usaha_id IS NULL AND ? IS NULL))
)
WHERE a.desa_id = ?
  AND a.status = 'aktif'
  AND a.tipe_akun IN ('pendapatan', 'beban')
  AND a.deleted_at IS NULL
ORDER BY a.kode_akun
```

---

## 📊 **STRUKTUR DATA HASIL**

### **Mode Bulanan:**
```php
[
    'mode' => 'bulanan',
    'periode' => '2026-01',
    'pendapatan' => 5000000.00,
    'beban' => 3000000.00,
    'laba_bersih' => 2000000.00,
    'detail_pendapatan' => [
        [
            'akun_id' => 37,
            'kode_akun' => '4-10',
            'nama_akun' => 'Pendapatan Jasa',
            'mutasi_debit' => 0,
            'mutasi_kredit' => 5000000,  // ✅ Mutasi bulan ini
            'jumlah' => 5000000,
        ],
    ],
    'detail_beban' => [
        [
            'akun_id' => 40,
            'kode_akun' => '5-10',
            'nama_akun' => 'Biaya Operasional',
            'mutasi_debit' => 3000000,   // ✅ Mutasi bulan ini
            'mutasi_kredit' => 0,
            'jumlah' => 3000000,
        ],
    ],
]
```

### **Mode Kumulatif:**
```php
[
    'mode' => 'kumulatif',
    'periode' => '2026-01',
    'pendapatan' => 15000000.00,  // ✅ Saldo akhir (kumulatif)
    'beban' => 8000000.00,        // ✅ Saldo akhir (kumulatif)
    'laba_bersih' => 7000000.00,
    'detail_pendapatan' => [
        [
            'akun_id' => 37,
            'kode_akun' => '4-10',
            'nama_akun' => 'Pendapatan Jasa',
            'saldo_akhir_debit' => 0,
            'saldo_akhir_kredit' => 15000000,  // ✅ Saldo akhir
            'jumlah' => 15000000,
        ],
    ],
    'detail_beban' => [
        [
            'akun_id' => 40,
            'kode_akun' => '5-10',
            'nama_akun' => 'Biaya Operasional',
            'saldo_akhir_debit' => 8000000,    // ✅ Saldo akhir
            'saldo_akhir_kredit' => 0,
            'jumlah' => 8000000,
        ],
    ],
]
```

---

## 🔄 **PERBANDINGAN: Method Lama vs Baru**

### **Method Lama (`getLabaRugi`):**
```php
// ❌ Query dari JURNAL
$neracaSaldo = $this->getNeracaSaldo($desaId, $bulan, $tahun, $unitUsahaId);
// ❌ Hanya total mutasi (bulanan)
// ❌ Tidak ada mode kumulatif
// ❌ Tidak menggunakan tabel neraca_saldo
```

### **Method Baru (`getLabaRugiFromLedger`):**
```php
// ✅ Query dari NERACA_SALDO (ledger)
Akun::leftJoin('neraca_saldo')
// ✅ Support 2 mode: 'bulanan' (mutasi) dan 'kumulatif' (saldo akhir)
// ✅ Menggunakan tabel neraca_saldo
// ✅ Format output lengkap dengan detail
```

---

## 🎯 **PERBEDAAN MODE**

### **Mode Bulanan (Mutasi):**
- ✅ Menampilkan **mutasi** pendapatan dan beban untuk bulan berjalan saja
- ✅ Berguna untuk melihat **kinerja bulanan**
- ✅ Tidak terpengaruh saldo bulan sebelumnya
- ✅ Contoh: Pendapatan bulan Januari = Rp 5.000.000

### **Mode Kumulatif (Saldo Akhir):**
- ✅ Menampilkan **saldo akhir** pendapatan dan beban (kumulatif)
- ✅ Berguna untuk melihat **total akumulasi** sampai periode tertentu
- ✅ Termasuk saldo dari bulan-bulan sebelumnya
- ✅ Contoh: Pendapatan sampai Januari = Rp 15.000.000 (akumulasi)

---

## 📋 **CONTOH OUTPUT**

### **Input:**
- Desa: Desa ABC
- Periode: 2026-01 (Januari 2026)
- Mode: Bulanan
- Unit Usaha: Semua Unit

### **Output Table (Mode Bulanan):**

```
┌─────────────────────────────────────────────────┐
│  LAPORAN LABA RUGI                              │
│  Periode: Januari 2026                          │
│  Mode: Bulanan (Mutasi Bulan Berjalan)          │
└─────────────────────────────────────────────────┘

PENDAPATAN
  4-10  Pendapatan Jasa              Rp  5.000.000
  4-20  Pendapatan Bunga Bank        Rp    250.000
  ────────────────────────────────────────────────
  Total Pendapatan                   Rp  5.250.000

BEBAN
  5-10  Biaya Operasional            Rp  2.000.000
  5-20  Biaya Administrasi           Rp    500.000
  5-30  Biaya Penyusutan             Rp  1.000.000
  ────────────────────────────────────────────────
  Total Beban                        Rp  3.500.000

LABA BERSIH                          Rp  1.750.000
```

### **Output Table (Mode Kumulatif):**

```
┌─────────────────────────────────────────────────┐
│  LAPORAN LABA RUGI                              │
│  Periode: Januari 2026                          │
│  Mode: Kumulatif (Saldo Akhir)                  │
└─────────────────────────────────────────────────┘

PENDAPATAN
  4-10  Pendapatan Jasa              Rp 15.000.000
  4-20  Pendapatan Bunga Bank        Rp  1.250.000
  ────────────────────────────────────────────────
  Total Pendapatan                   Rp 16.250.000

BEBAN
  5-10  Biaya Operasional            Rp  8.000.000
  5-20  Biaya Administrasi           Rp  2.500.000
  5-30  Biaya Penyusutan             Rp  6.000.000
  ────────────────────────────────────────────────
  Total Beban                        Rp 16.500.000

RUGI BERSIH                          Rp   -250.000
```

---

## 🔄 **FLOW DATA**

```
┌─────────────────────────────────────────┐
│  User Input                             │
│  - Desa, Unit Usaha, Bulan, Tahun       │
│  - Mode: Bulanan atau Kumulatif         │
└───────────────┬─────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────┐
│  Convert to Periode (YYYY-MM)            │
│  Example: 2026-01                        │
└───────────────┬─────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────┐
│  AccountingService::getLabaRugiFromLedger() │
│  - Query: akun LEFT JOIN neraca_saldo    │
│  - Filter: tipe_akun IN (pendapatan, beban) │
│  - Mode: bulanan (mutasi) atau kumulatif (saldo akhir) │
└───────────────┬─────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────┐
│  Calculate                                │
│  - Total Pendapatan                       │
│  - Total Beban                            │
│  - Laba Bersih = Pendapatan - Beban       │
└───────────────┬─────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────┐
│  Return Array dengan Format Lengkap      │
│  - mode, periode                         │
│  - pendapatan, beban, laba_bersih        │
│  - detail_pendapatan, detail_beban       │
└───────────────┬─────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────┐
│  View (Blade)                            │
│  - Display table dengan mode info        │
│  - Show pendapatan dan beban             │
│  - Show laba/rugi bersih                 │
└─────────────────────────────────────────┘
```

---

## ✅ **FITUR YANG SUDAH DIIMPLEMENTASIKAN**

1. ✅ **Query dari Ledger** - Menggunakan tabel `neraca_saldo`
2. ✅ **Mode Bulanan** - Menggunakan mutasi bulan berjalan
3. ✅ **Mode Kumulatif** - Menggunakan saldo akhir
4. ✅ **Periode YYYY-MM** - Format standar
5. ✅ **Multi Unit Usaha** - Support filter per unit
6. ✅ **Detail per Akun** - List pendapatan dan beban
7. ✅ **Hitung Laba Bersih** - Total Pendapatan - Total Beban
8. ✅ **Mode Selector** - Dropdown untuk pilih mode
9. ✅ **Info Box** - Penjelasan mode yang dipilih
10. ✅ **Export PDF** - Support export dengan format lengkap

---

## 🚀 **CARA MENGGUNAKAN**

1. **Login ke sistem**
2. **Klik menu: Laporan > Laba Rugi**
3. **Pilih:**
   - Desa (jika Super Admin/Admin Kecamatan)
   - Unit Usaha (optional)
   - Bulan
   - Tahun
   - **Mode: Bulanan atau Kumulatif** ← **BARU!**
4. **Klik "Lihat Laporan"**
5. **Table akan menampilkan:**
   - ✅ **Mode Bulanan**: Mutasi pendapatan & beban bulan ini
   - ✅ **Mode Kumulatif**: Saldo akhir kumulatif sampai periode ini
6. **Klik "Export PDF"** untuk download laporan

---

## 📝 **CATATAN PENTING**

1. **Mode Bulanan** menampilkan mutasi bulan berjalan saja
2. **Mode Kumulatif** menampilkan saldo akhir (termasuk bulan sebelumnya)
3. **Pendapatan** normal kredit, jadi jumlah = mutasi_kredit (bulanan) atau saldo_akhir_kredit (kumulatif)
4. **Beban** normal debit, jadi jumlah = mutasi_debit (bulanan) atau saldo_akhir_debit (kumulatif)
5. **Laba Bersih** = Total Pendapatan - Total Beban
6. **Jika negatif** = Rugi Bersih

---

## 🔧 **TROUBLESHOOTING**

### **Q: Pendapatan/Beban tidak tampil?**
**A:** Pastikan:
- Akun memiliki status `aktif`
- Akun memiliki tipe `pendapatan` atau `beban`
- Ada transaksi yang sudah di-post untuk periode tersebut

### **Q: Mode Bulanan = 0 padahal ada transaksi?**
**A:** 
- Pastikan transaksi sudah di-post (status = 'posted')
- Pastikan periode transaksi sesuai dengan periode laporan
- Jalankan `recalculateBalance()` untuk periode tersebut

### **Q: Mode Kumulatif tidak sesuai?**
**A:**
- Pastikan periode sebelumnya sudah di-close
- Pastikan saldo awal sudah terisi dengan benar
- Jalankan `recalculateBalance()` untuk semua periode

---

## ✅ **KESIMPULAN**

**Implementasi SUDAH LENGKAP dan SIAP DIGUNAKAN!**

Semua requirement sudah terpenuhi:
- ✅ Berdasarkan tabel neraca_saldo
- ✅ Laba Rugi Bulanan (mutasi)
- ✅ Laba Rugi Kumulatif (saldo akhir)
- ✅ Hitung Laba Bersih
- ✅ Output Service
- ✅ Query
- ✅ Struktur data hasil

**Sistem siap untuk production!** 🎉
