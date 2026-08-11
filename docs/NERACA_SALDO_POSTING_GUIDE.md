# 📊 Panduan: Kapan Data Neraca Saldo Terisi?

## ✅ **SOLUSI SUDAH DIIMPLEMENTASIKAN**

Sistem sekarang **otomatis posting ke neraca_saldo** saat:
1. ✅ Jurnal dibuat dengan status `posted`
2. ✅ Jurnal diubah status dari `draft` ke `posted`

---

## 🔄 **ALUR POSTING OTOMATIS**

### **1. Saat Jurnal Dibuat (createJurnal)**

```
User membuat transaksi (Kas/Memorial)
    ↓
AccountingService::createJurnal()
    ↓
Jurnal dibuat dengan status 'posted'
    ↓
Auto-trigger: postToLedger()
    ↓
Data masuk ke neraca_saldo ✅
```

### **2. Saat Jurnal Di-Post (postJurnal)**

```
User mengubah status jurnal dari 'draft' ke 'posted'
    ↓
AccountingService::postJurnal()
    ↓
Status diubah menjadi 'posted'
    ↓
Auto-trigger: postToLedger()
    ↓
Data masuk ke neraca_saldo ✅
```

### **3. Saat Jurnal Di-Update (Model Observer)**

```
User mengubah status jurnal via update()
    ↓
Model Observer: updated()
    ↓
Deteksi status berubah menjadi 'posted'
    ↓
Auto-trigger: postToLedger()
    ↓
Data masuk ke neraca_saldo ✅
```

---

## 📋 **KAPAN DATA TERISI?**

Data akan **otomatis terisi** ketika:

1. ✅ **Membuat Transaksi Kas** (`/kas/create`)
   - Jurnal otomatis dibuat dengan status `posted`
   - Auto-post ke `neraca_saldo`

2. ✅ **Membuat Jurnal Memorial** (`/memorial/create`)
   - Jika status = `posted`, auto-post ke `neraca_saldo`

3. ✅ **Mengubah Status Jurnal** dari `draft` ke `posted`
   - Auto-post ke `neraca_saldo`

4. ✅ **Membuat Pinjaman/Angsuran**
   - Jurnal otomatis dibuat dengan status `posted`
   - Auto-post ke `neraca_saldo`

5. ✅ **Membuat Saldo Awal Kas**
   - Jurnal otomatis dibuat dengan status `posted`
   - Auto-post ke `neraca_saldo`

---

## 🔧 **UNTUK DATA YANG SUDAH ADA**

Jika sudah ada jurnal yang dibuat **sebelum** implementasi auto-post, perlu **manual posting**:

### **Cara 1: Via Halaman Periode**

1. Buka `/periode`
2. Pilih periode yang ingin di-post (contoh: 2026-01)
3. Klik "Recalculate Balance" atau "Post to Ledger"

### **Cara 2: Via Tinker**

```php
php artisan tinker

$accountingService = app(\App\Services\AccountingService::class);
$accountingService->recalculateBalance(5, '2026-01', null); // desa_id, periode, unit_usaha_id
```

### **Cara 3: Via Command (jika dibuat)**

```bash
php artisan accounting:post-period {desa_id} {periode}
```

---

## 🎯 **CONTOH ALUR LENGKAP**

### **Skenario: User membuat transaksi kas**

1. User login sebagai Admin Desa
2. Buka `/kas/create`
3. Input:
   - Tanggal: 2026-01-15
   - Jenis: Masuk
   - Jumlah: Rp 1.000.000
   - Akun Kas: Kas
   - Akun Lawan: Pendapatan Jasa
4. Klik "Simpan"
5. **Sistem otomatis:**
   - ✅ Membuat `TransaksiKas`
   - ✅ Membuat `Jurnal` (status: posted)
   - ✅ Auto-post ke `neraca_saldo` (periode: 2026-01)
6. **Data sekarang tersedia di:**
   - ✅ `/laporan/neraca-saldo` (periode: 2026-01)
   - ✅ `/laporan/laba-rugi` (periode: 2026-01)
   - ✅ `/laporan/neraca` (periode: 2026-01)

---

## ⚠️ **CATATAN PENTING**

1. **Periode Berbasis Bulan**
   - Format: `YYYY-MM` (contoh: `2026-01`)
   - Data di-group berdasarkan bulan

2. **Saldo Awal Otomatis**
   - Saldo awal bulan ini = saldo akhir bulan lalu
   - Otomatis dihitung saat posting pertama kali

3. **Multi Unit Usaha**
   - Jika ada `unit_usaha_id`, data di-post per unit
   - Jika `null`, data untuk semua unit

4. **Recalculate Balance**
   - Gunakan `recalculateBalance()` untuk re-post semua jurnal periode tertentu
   - Berguna saat ada koreksi atau perubahan

---

## ✅ **KESIMPULAN**

**Data akan otomatis terisi** saat:
- ✅ Membuat transaksi baru (Kas/Memorial)
- ✅ Mengubah status jurnal ke 'posted'
- ✅ Membuat pinjaman/angsuran

**Untuk data lama**, perlu manual posting via:
- Halaman Periode
- Tinker
- Command (jika dibuat)

**Sistem sekarang sudah otomatis!** 🎉
