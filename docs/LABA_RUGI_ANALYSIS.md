# 📊 Analisa Implementasi Laporan Laba Rugi

## 🔍 **STATUS IMPLEMENTASI SAAT INI**

### ❌ **BELUM SESUAI REQUIREMENT**

**Method `getLabaRugi()` saat ini:**
- ❌ Masih menggunakan `getNeracaSaldo()` yang query dari **JURNAL**
- ❌ **BELUM menggunakan tabel `neraca_saldo`** (ledger)
- ❌ **BELUM support Laba Rugi Bulanan** (mutasi)
- ❌ **BELUM support Laba Rugi Kumulatif** (saldo akhir)
- ❌ Hanya menghitung dari `saldo` (total mutasi)

---

## 📋 **REQUIREMENT vs IMPLEMENTASI**

| Requirement | Status | Keterangan |
|------------|--------|------------|
| **Berdasarkan tabel neraca_saldo** | ❌ | Masih dari jurnal |
| **Laba Rugi Bulanan (mutasi)** | ❌ | Belum ada |
| **Laba Rugi Kumulatif (saldo akhir)** | ❌ | Belum ada |
| **Hitung Laba Bersih** | ✅ | Sudah ada (tapi dari jurnal) |
| **Output Service** | ✅ | Sudah ada (tapi perlu update) |
| **Query** | ❌ | Perlu query baru dari neraca_saldo |
| **Struktur data hasil** | ⚠️ | Perlu ditambahkan mode (bulanan/kumulatif) |

---

## 🎯 **SOLUSI: BUAT METHOD BARU**

### **Method yang Perlu Dibuat:**

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
    string $mode = 'bulanan',  // 'bulanan' atau 'kumulatif'
    ?int $unitUsahaId = null
): array
```

### **Query Logic:**

**Mode Bulanan (mutasi):**
```sql
SELECT 
    a.id, a.kode_akun, a.nama_akun,
    COALESCE(ns.mutasi_debit, 0) as mutasi_debit,
    COALESCE(ns.mutasi_kredit, 0) as mutasi_kredit
FROM akun a
LEFT JOIN neraca_saldo ns ON (
    ns.akun_id = a.id 
    AND ns.desa_id = ? 
    AND ns.periode = ?
    AND (ns.unit_usaha_id = ? OR (ns.unit_usaha_id IS NULL AND ? IS NULL))
)
WHERE a.desa_id = ?
  AND a.tipe_akun IN ('pendapatan', 'beban')
  AND a.status = 'aktif'
ORDER BY a.kode_akun
```

**Mode Kumulatif (saldo akhir):**
```sql
SELECT 
    a.id, a.kode_akun, a.nama_akun,
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
  AND a.tipe_akun IN ('pendapatan', 'beban')
  AND a.status = 'aktif'
ORDER BY a.kode_akun
```

---

## 📊 **FORMAT OUTPUT YANG DIPERLUKAN**

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
// ✅ Support 2 mode: 'bulanan' (mutasi) dan 'kumulatif' (saldo akhir)
// ✅ Menggunakan tabel neraca_saldo
// ✅ Format output lengkap dengan detail
```

---

## ✅ **KESIMPULAN**

### **Status:**
- ❌ **BELUM SESUAI REQUIREMENT**
- ⚠️ **Infrastruktur ada** (tabel neraca_saldo, model, posting logic)
- ❌ **Query/Service BELUM** (masih dari jurnal, belum support 2 mode)
- ❌ **Format output BELUM LENGKAP** (belum ada mode, belum ada detail mutasi/saldo akhir)

### **Action Required:**
1. ✅ Buat method baru `getLabaRugiFromLedger()`
2. ✅ Support 2 mode: 'bulanan' dan 'kumulatif'
3. ✅ Update Livewire component untuk menggunakan method baru
4. ✅ Update view untuk menampilkan mode selector dan format lengkap

---

**Apakah Anda ingin saya implementasikan method baru ini sekarang?** 🚀
