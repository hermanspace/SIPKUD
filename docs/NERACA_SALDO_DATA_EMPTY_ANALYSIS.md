# 🔍 Analisa: Kapan Data Neraca Saldo Terisi?

## ❌ **MASALAH YANG DITEMUKAN**

Laporan berikut masih kosong:
- `/laporan/neraca-saldo`
- `/laporan/laba-rugi`
- `/laporan/neraca`

**Penyebab:** Data belum ter-post ke tabel `neraca_saldo`.

---

## 🔄 **ALUR POSTING KE NERACA SALDO**

### **Cara 1: Auto-Post saat Jurnal Dibuat (BELUM ADA)**

Saat ini, jurnal dibuat dengan status `posted`, tapi **TIDAK otomatis ter-post ke neraca_saldo**.

**Yang Seharusnya:**
```
Jurnal dibuat (status: posted)
    ↓
Auto-trigger: postToLedger()
    ↓
Data masuk ke neraca_saldo
```

**Yang Terjadi Sekarang:**
```
Jurnal dibuat (status: posted)
    ↓
❌ TIDAK ada auto-trigger
    ↓
Data TIDAK masuk ke neraca_saldo
```

### **Cara 2: Manual Post via recalculateBalance()**

Data akan terisi jika memanggil `recalculateBalance()` untuk periode tertentu.

**Cara:**
```php
$accountingService->recalculateBalance($desaId, '2026-01', $unitUsahaId);
```

---

## ✅ **SOLUSI**

### **Opsi 1: Tambahkan Auto-Post saat Jurnal Dibuat (RECOMMENDED)**

Tambahkan observer/event di model `Jurnal` untuk auto-post ke ledger saat status menjadi 'posted'.

**File:** `app/Models/Jurnal.php`

```php
protected static function boot()
{
    parent::boot();

    static::creating(function ($jurnal) {
        if (!$jurnal->nomor_jurnal) {
            $jurnal->nomor_jurnal = static::generateNomorJurnal($jurnal->desa_id);
        }
    });

    // Auto-post ke ledger saat status menjadi 'posted'
    static::updated(function ($jurnal) {
        if ($jurnal->isDirty('status') && $jurnal->status === 'posted') {
            app(AccountingService::class)->postToLedger($jurnal);
        }
    });
}
```

### **Opsi 2: Post Manual via AccountingService**

Panggil `postToLedger()` setelah jurnal dibuat, atau `recalculateBalance()` untuk posting semua jurnal periode tertentu.

**Contoh di AccountingService::createJurnal():**
```php
// Setelah jurnal dibuat
if ($jurnal->status === 'posted') {
    $this->postToLedger($jurnal);
}
```

### **Opsi 3: Post via Halaman Periode**

Gunakan halaman `/periode` untuk posting manual:
1. Buka halaman Periode Akuntansi
2. Pilih periode yang ingin di-post
3. Klik "Recalculate Balance" atau "Post to Ledger"

---

## 📋 **KAPAN DATA TERISI?**

Data akan terisi ketika:

1. ✅ **Jurnal dibuat dengan status 'posted'** (jika ada auto-post)
2. ✅ **Manual post via recalculateBalance()** untuk periode tertentu
3. ✅ **Via halaman Periode Akuntansi** (jika sudah diimplementasikan)

---

## 🚀 **TINDAK LANJUT**

1. ✅ **Tambahkan auto-post** saat jurnal dibuat (Opsi 1 - RECOMMENDED)
2. ✅ **Update AccountingService::createJurnal()** untuk auto-post (Opsi 2)
3. ✅ **Buat command/button** untuk posting manual (Opsi 3)
4. ✅ **Jalankan recalculateBalance()** untuk data yang sudah ada

---

## 🔧 **UNTUK DATA YANG SUDAH ADA**

Jika sudah ada jurnal yang dibuat sebelumnya, jalankan:

```php
// Via tinker atau command
$accountingService = app(AccountingService::class);
$accountingService->recalculateBalance($desaId, '2026-01', null);
```

Atau buat command:
```bash
php artisan accounting:post-period {desa_id} {periode}
```
