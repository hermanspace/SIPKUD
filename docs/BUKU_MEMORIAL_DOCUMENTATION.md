# 📘 Dokumentasi Buku Memorial - Transaksi Non-Kas

## 📊 ANALISA IMPLEMENTASI

### ✅ **SUDAH DIIMPLEMENTASIKAN LENGKAP**

Modul **Buku Memorial** untuk transaksi non-kas sudah diimplementasikan dengan approach **Livewire Full-Stack Components** dan terintegrasi penuh dengan `AccountingService`.

---

## ✅ Requirement Checklist

| Requirement | Status | Implementasi |
|------------|--------|--------------|
| **Transaksi Non-Kas** | ✅ DONE | `jenis_jurnal` = `'memorial'` |
| **Penyusutan Aset** | ✅ DONE | Multi-akun support |
| **Bunga Bank** | ✅ DONE | Multi-akun support |
| **Pajak Bank** | ✅ DONE | Multi-akun support |
| **Transfer Antar Rekening** | ✅ DONE | Multi-akun support |
| **Tidak Pengaruhi Kas** | ✅ DONE | Tidak ada `transaksi_kas_id` |
| **Wajib Debit = Kredit** | ✅ DONE | Validasi di `AccountingService` |
| **Multi Akun per Transaksi** | ✅ DONE | Array `details[]` unlimited |

---

## 🏗️ Arsitektur Implementasi

### 1️⃣ **Model: `Jurnal`**

```php
// app/Models/Jurnal.php
protected $fillable = [
    'desa_id',
    'unit_usaha_id',
    'nomor_jurnal',             // Auto-generated: JRN/YYYY/MM/XXXXX
    'tanggal_transaksi',
    'jenis_jurnal',             // 'memorial', 'kas_harian', dll
    'keterangan',
    'total_debit',
    'total_kredit',
    'status',                   // 'draft', 'posted', 'void'
];

// Auto-generate nomor jurnal
protected static function boot() {
    static::creating(function ($jurnal) {
        $jurnal->nomor_jurnal = static::generateNomorJurnal($jurnal->desa_id);
    });
}
```

### 2️⃣ **Model: `JurnalDetail`**

```php
// app/Models/JurnalDetail.php
protected $fillable = [
    'jurnal_id',
    'akun_id',           // Support ANY akun (aset, liability, equity, revenue, expense)
    'posisi',            // 'debit' atau 'kredit'
    'jumlah',
    'keterangan',
];
```

### 3️⃣ **Livewire Component: `Memorial/Create.php`**

**Features:**
- ✅ Multi-row entry (unlimited)
- ✅ Dynamic add/remove row
- ✅ Real-time balance calculation
- ✅ Validation debit = kredit

**Validation (Lines 69-87):**
```php
$this->validate([
    'tanggal_transaksi' => 'required|date',
    'keterangan' => 'required|string|max:1000',
    'details' => 'required|array|min:2',           // ✅ Minimal 2 baris
    'details.*.akun_id' => 'required|exists:akun,id',
    'details.*.posisi' => 'required|in:debit,kredit',
    'details.*.jumlah' => 'required|numeric|min:0.01',
]);
```

**Dynamic Balance Check (Lines 51-63):**
```php
public function getTotalDebitProperty() {
    return collect($this->details)
        ->filter(fn($d) => ($d['posisi'] ?? '') === 'debit')
        ->sum(fn($d) => floatval($d['jumlah'] ?? 0));
}

public function getTotalKreditProperty() {
    return collect($this->details)
        ->filter(fn($d) => ($d['posisi'] ?? '') === 'kredit')
        ->sum(fn($d) => floatval($d['jumlah'] ?? 0));
}
```

**Save via AccountingService (Lines 98-106):**
```php
$accountingService->createJurnal([
    'desa_id' => $user->desa_id,
    'unit_usaha_id' => $this->unit_usaha_id,
    'tanggal_transaksi' => $this->tanggal_transaksi,
    'jenis_jurnal' => 'memorial',              // ✅ Non-kas
    'keterangan' => $this->keterangan,
    'status' => $this->status,                 // 'posted' atau 'draft'
    'details' => $validDetails,                // ✅ Multi-akun array
]);
```

---

## 📋 Contoh Implementasi

### 🔧 **Contoh 1: Penyusutan Aset Bulanan**

#### Skenario:
BUM Desa memiliki kendaraan senilai Rp 60.000.000 dengan masa manfaat 5 tahun (60 bulan).
- **Penyusutan bulanan**: Rp 60.000.000 / 60 = **Rp 1.000.000**
- **Akun Debit**: 5-40 Biaya Penyusutan
- **Akun Kredit**: 1-30 Akumulasi Penyusutan

#### Input di Form Memorial:

```
Tanggal       : 2026-01-31
Unit Usaha    : USP (Unit Simpan Pinjam)
Keterangan    : Penyusutan Kendaraan Operasional - Januari 2026

Detail Jurnal:
┌──────────────────────────────────┬────────┬─────────────────┐
│ Akun                             │ Posisi │ Jumlah          │
├──────────────────────────────────┼────────┼─────────────────┤
│ 5-40 Biaya Penyusutan            │ Debit  │ Rp 1.000.000    │
│ 1-30 Akumulasi Penyusutan        │ Kredit │ Rp 1.000.000    │
└──────────────────────────────────┴────────┴─────────────────┘

Total Debit  : Rp 1.000.000
Total Kredit : Rp 1.000.000
Balance      : ✅ VALID
```

#### Payload Data (Internal):

```php
[
    'desa_id' => 5,
    'unit_usaha_id' => 1,
    'tanggal_transaksi' => '2026-01-31',
    'jenis_jurnal' => 'memorial',
    'keterangan' => 'Penyusutan Kendaraan Operasional - Januari 2026',
    'status' => 'posted',
    'details' => [
        [
            'akun_id' => 45,  // 5-40 Biaya Penyusutan
            'posisi' => 'debit',
            'jumlah' => 1000000,
            'keterangan' => 'Biaya penyusutan kendaraan 1 bulan'
        ],
        [
            'akun_id' => 13,  // 1-30 Akumulasi Penyusutan
            'posisi' => 'kredit',
            'jumlah' => 1000000,
            'keterangan' => 'Akumulasi penyusutan kendaraan'
        ],
    ],
]
```

#### Hasil di Database:

**Tabel `jurnal`:**
```
id  | nomor_jurnal      | tanggal_transaksi | jenis_jurnal | total_debit | total_kredit | status
----|-------------------|-------------------|--------------|-------------|--------------|--------
150 | JRN/2026/01/00150 | 2026-01-31        | memorial     | 1000000.00  | 1000000.00   | posted
```

**Tabel `jurnal_detail`:**
```
id  | jurnal_id | akun_id | posisi | jumlah     | keterangan
----|-----------|---------|--------|------------|----------------------------------
301 | 150       | 45      | debit  | 1000000.00 | Biaya penyusutan kendaraan 1 bulan
302 | 150       | 13      | kredit | 1000000.00 | Akumulasi penyusutan kendaraan
```

---

### 💰 **Contoh 2: Bunga Bank Masuk**

#### Skenario:
Bank memberikan bunga rekening BUM Desa sebesar Rp 250.000 dipotong pajak 20%.
- **Bunga Kotor**: Rp 250.000
- **Pajak (20%)**: Rp 50.000
- **Bunga Netto**: Rp 200.000

#### Input di Form Memorial:

```
Tanggal       : 2026-01-31
Unit Usaha    : UMUM
Keterangan    : Bunga Bank Januari 2026 (Dipotong Pajak 20%)

Detail Jurnal:
┌──────────────────────────────────┬────────┬─────────────────┐
│ Akun                             │ Posisi │ Jumlah          │
├──────────────────────────────────┼────────┼─────────────────┤
│ 1-11 Bank BRI                    │ Debit  │ Rp   200.000    │
│ 1-40 Pajak Dibayar Dimuka        │ Debit  │ Rp    50.000    │
│ 4-20 Pendapatan Bunga Bank       │ Kredit │ Rp   250.000    │
└──────────────────────────────────┴────────┴─────────────────┘

Total Debit  : Rp 250.000
Total Kredit : Rp 250.000
Balance      : ✅ VALID
```

#### Payload Data (Internal):

```php
[
    'desa_id' => 5,
    'unit_usaha_id' => null,  // UMUM
    'tanggal_transaksi' => '2026-01-31',
    'jenis_jurnal' => 'memorial',
    'keterangan' => 'Bunga Bank Januari 2026 (Dipotong Pajak 20%)',
    'status' => 'posted',
    'details' => [
        [
            'akun_id' => 2,   // 1-11 Bank BRI
            'posisi' => 'debit',
            'jumlah' => 200000,
            'keterangan' => 'Bunga netto masuk rekening'
        ],
        [
            'akun_id' => 14,  // 1-40 Pajak Dibayar Dimuka
            'posisi' => 'debit',
            'jumlah' => 50000,
            'keterangan' => 'PPh Final 20% atas bunga'
        ],
        [
            'akun_id' => 37,  // 4-20 Pendapatan Bunga Bank
            'posisi' => 'kredit',
            'jumlah' => 250000,
            'keterangan' => 'Pendapatan bunga bank bruto'
        ],
    ],
]
```

---

### 🔄 **Contoh 3: Transfer Antar Rekening**

#### Skenario:
Transfer dari Bank BRI ke Kas untuk operasional sebesar Rp 5.000.000.

#### Input di Form Memorial:

```
Tanggal       : 2026-01-23
Unit Usaha    : USP
Keterangan    : Transfer dari Bank BRI ke Kas untuk operasional

Detail Jurnal:
┌──────────────────────────────────┬────────┬─────────────────┐
│ Akun                             │ Posisi │ Jumlah          │
├──────────────────────────────────┼────────┼─────────────────┤
│ 1-10 Kas                         │ Debit  │ Rp 5.000.000    │
│ 1-11 Bank BRI                    │ Kredit │ Rp 5.000.000    │
└──────────────────────────────────┴────────┴─────────────────┘

Total Debit  : Rp 5.000.000
Total Kredit : Rp 5.000.000
Balance      : ✅ VALID
```

#### Payload Data (Internal):

```php
[
    'desa_id' => 5,
    'unit_usaha_id' => 1,
    'tanggal_transaksi' => '2026-01-23',
    'jenis_jurnal' => 'memorial',
    'keterangan' => 'Transfer dari Bank BRI ke Kas untuk operasional',
    'status' => 'posted',
    'details' => [
        [
            'akun_id' => 1,   // 1-10 Kas
            'posisi' => 'debit',
            'jumlah' => 5000000,
            'keterangan' => 'Penerimaan dari bank'
        ],
        [
            'akun_id' => 2,   // 1-11 Bank BRI
            'posisi' => 'kredit',
            'jumlah' => 5000000,
            'keterangan' => 'Transfer ke kas'
        ],
    ],
]
```

---

### 🧾 **Contoh 4: Pajak Bank (Biaya Administrasi)**

#### Skenario:
Bank mengenakan biaya administrasi Rp 15.000 per bulan.

#### Input di Form Memorial:

```
Tanggal       : 2026-01-31
Unit Usaha    : UMUM
Keterangan    : Biaya Administrasi Bank Januari 2026

Detail Jurnal:
┌──────────────────────────────────┬────────┬─────────────────┐
│ Akun                             │ Posisi │ Jumlah          │
├──────────────────────────────────┼────────┼─────────────────┤
│ 5-30 Biaya Administrasi Bank     │ Debit  │ Rp    15.000    │
│ 1-11 Bank BRI                    │ Kredit │ Rp    15.000    │
└──────────────────────────────────┴────────┴─────────────────┘

Total Debit  : Rp 15.000
Total Kredit : Rp 15.000
Balance      : ✅ VALID
```

---

### 📊 **Contoh 5: Jurnal Penyesuaian (Multi-Akun)**

#### Skenario:
Akhir bulan, mencatat:
- Penyusutan kendaraan: Rp 1.000.000
- Penyusutan komputer: Rp 500.000
- Total: Rp 1.500.000

#### Input di Form Memorial:

```
Tanggal       : 2026-01-31
Unit Usaha    : UMUM
Keterangan    : Jurnal Penyesuaian - Januari 2026

Detail Jurnal:
┌──────────────────────────────────┬────────┬─────────────────┐
│ Akun                             │ Posisi │ Jumlah          │
├──────────────────────────────────┼────────┼─────────────────┤
│ 5-40 Biaya Penyusutan            │ Debit  │ Rp 1.500.000    │
│ 1-31 Akm. Peny. Kendaraan        │ Kredit │ Rp 1.000.000    │
│ 1-32 Akm. Peny. Peralatan        │ Kredit │ Rp   500.000    │
└──────────────────────────────────┴────────┴─────────────────┘

Total Debit  : Rp 1.500.000
Total Kredit : Rp 1.500.000
Balance      : ✅ VALID
```

#### Payload Data (Internal):

```php
[
    'details' => [
        [
            'akun_id' => 45,  // 5-40 Biaya Penyusutan
            'posisi' => 'debit',
            'jumlah' => 1500000,
            'keterangan' => 'Total biaya penyusutan bulan ini'
        ],
        [
            'akun_id' => 13,  // 1-31 Akumulasi Penyusutan Kendaraan
            'posisi' => 'kredit',
            'jumlah' => 1000000,
            'keterangan' => 'Penyusutan kendaraan'
        ],
        [
            'akun_id' => 14,  // 1-32 Akumulasi Penyusutan Peralatan
            'posisi' => 'kredit',
            'jumlah' => 500000,
            'keterangan' => 'Penyusutan komputer & printer'
        ],
    ],
]
```

---

## 🔄 Flow Transaksi Memorial

```
┌─────────────────────────────────────────┐
│  User Input (Livewire Form)            │
│  - Tanggal, Keterangan                  │
│  - Multiple Rows (Akun, D/K, Jumlah)   │
└───────────────┬─────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────┐
│  Real-time Balance Check                │
│  - Total Debit = Total Kredit?          │
│  - Display balance indicator            │
└───────────────┬─────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────┐
│  Validation                             │
│  - Required fields                      │
│  - Minimal 2 rows                       │
│  - All akun must exist                  │
└───────────────┬─────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────┐
│  AccountingService::createJurnal()      │
│  - Validate debit = kredit (strict)     │
│  - Calculate totals                     │
│  - Auto-generate nomor_jurnal           │
│  - Save to jurnal & jurnal_detail       │
└───────────────┬─────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────┐
│  Post to Ledger                         │
│  - Update neraca_saldo for period       │
│  - Calculate saldo akhir                │
│  - NO impact on transaksi_kas table     │
└─────────────────────────────────────────┘
```

---

## 🎯 Perbedaan: Kas Harian vs Buku Memorial

| Aspek | Kas Harian | Buku Memorial |
|-------|------------|---------------|
| **Tipe Transaksi** | Kas (masuk/keluar) | Non-kas |
| **Sumber** | `TransaksiKas` model | Direct `Jurnal` |
| **Jumlah Baris** | Fixed 2 baris (D & K) | Multi-baris (unlimited) |
| **Akun Kas** | Wajib ada | Tidak wajib |
| **Use Case** | Penerimaan/pengeluaran kas | Penyusutan, bunga, transfer |
| **Auto D-K** | Ya (fixed pattern) | Manual (flexible) |

---

## 📁 File Struktur

### Migration:
- `database/migrations/2025_12_26_100001_create_jurnal_table.php`
- `database/migrations/2025_12_26_100002_create_jurnal_detail_table.php`

### Model:
- `app/Models/Jurnal.php`
- `app/Models/JurnalDetail.php`

### Livewire Components:
- `app/Livewire/Memorial/Index.php`
- `app/Livewire/Memorial/Create.php`
- `app/Livewire/Memorial/Edit.php`

### Views:
- `resources/views/livewire/memorial/index.blade.php`
- `resources/views/livewire/memorial/create.blade.php`
- `resources/views/livewire/memorial/edit.blade.php`

### Routes:
```php
Route::get('memorial', \App\Livewire\Memorial\Index::class)->name('memorial.index');
Route::get('memorial/create', \App\Livewire\Memorial\Create::class)->name('memorial.create');
Route::get('memorial/{id}/edit', \App\Livewire\Memorial\Edit::class)->name('memorial.edit');
```

---

## ✅ Kesimpulan

### **IMPLEMENTASI SUDAH LENGKAP** ✅

Semua requirement yang diminta **SUDAH DIIMPLEMENTASIKAN**:

| Requirement | Status |
|------------|--------|
| ✅ Transaksi non-kas | DONE |
| ✅ Penyusutan aset | DONE (contoh tersedia) |
| ✅ Bunga bank | DONE (contoh tersedia) |
| ✅ Pajak bank | DONE (contoh tersedia) |
| ✅ Transfer antar rekening | DONE (contoh tersedia) |
| ✅ Tidak pengaruhi saldo kas | DONE |
| ✅ Wajib debit = kredit | DONE (validasi strict) |
| ✅ Multi akun per transaksi | DONE (unlimited rows) |

### Fitur Tambahan yang Sudah Ada:
- ✅ Auto-generate nomor jurnal (JRN/YYYY/MM/XXXXX)
- ✅ Real-time balance check di UI
- ✅ Dynamic add/remove row
- ✅ Draft & Posted status
- ✅ Void jurnal capability
- ✅ Audit trail (created_by, updated_by)
- ✅ Soft delete support
- ✅ Integrasi penuh dengan AccountingService
- ✅ Auto post to ledger (neraca_saldo)

---

## 🚀 Cara Penggunaan

1. **Login sebagai Admin Desa**
2. **Klik menu "Akuntansi > Buku Memorial"**
3. **Klik "Tambah"**
4. **Isi form:**
   - Tanggal transaksi
   - Unit usaha (optional)
   - Keterangan
   - Detail jurnal (minimal 2 baris)
5. **Pastikan Total Debit = Total Kredit**
6. **Simpan**
7. **Jurnal otomatis ter-post ke ledger**

---

## 📝 Catatan Penting

1. **Setiap jurnal memorial harus balance** (debit = kredit)
2. **Minimal 2 baris** (1 debit, 1 kredit)
3. **Unlimited baris** untuk jurnal penyesuaian kompleks
4. **Tidak mempengaruhi tabel `transaksi_kas`** (pure journal entry)
5. **Nomor jurnal auto-generated** dan unique per bulan
6. **Status 'posted' langsung update neraca_saldo**
7. **Support multi-unit usaha** untuk alokasi per unit

---

**Sistem siap digunakan!** 🎉
