# 📋 Analisa Integrasi Pinjaman & Angsuran dengan Akuntansi

## 🔍 **STATUS SAAT INI**

### ✅ **Yang Sudah Ada:**
1. **Model Pinjaman & AngsuranPinjaman** - ✅ Ada
2. **Relasi ke TransaksiKas** - ✅ Ada (`pinjaman_id`, `angsuran_pinjaman_id`)
3. **Relasi ke Jurnal** - ✅ Ada (`pinjaman_id`, `angsuran_pinjaman_id`)
4. **Auto-create TransaksiKas** - ✅ Ada (tapi **BELUM LENGKAP**)

### ❌ **Yang Belum Ada:**
1. **Seeder untuk Pinjaman & Angsuran** - ❌ Tidak ada
2. **Auto-create Jurnal dari Pinjaman** - ❌ Belum ada
3. **Auto-create Jurnal dari Angsuran** - ❌ Belum ada
4. **Akun otomatis untuk Pinjaman** - ❌ Belum ada
5. **Akun otomatis untuk Angsuran** - ❌ Belum ada

---

## 🔄 **ALUR YANG SEHARUSNYA**

### **1. PINJAMAN (Kas Keluar)**
```
User Input Pinjaman
    ↓
Pinjaman::created event
    ↓
TransaksiKas::create (keluar)
    ↓
❌ BELUM: Auto-create Jurnal dengan:
    - Debit: Piutang Pinjaman Anggota
    - Kredit: Kas
```

### **2. ANGSURAN (Kas Masuk)**
```
User Input Angsuran
    ↓
AngsuranPinjaman::created event
    ↓
TransaksiKas::create (masuk)
    ↓
❌ BELUM: Auto-create Jurnal dengan:
    - Debit: Kas
    - Kredit: Piutang Pinjaman Anggota (pokok)
    - Kredit: Pendapatan Jasa Pinjaman (jasa)
    - Kredit: Pendapatan Denda (denda, jika ada)
```

---

## 📊 **MASALAH YANG DITEMUKAN**

### **1. TransaksiKas dari Pinjaman/Angsuran tidak punya akun:**
```php
// Di Pinjaman::boot()
TransaksiKas::create([
    'desa_id' => $pinjaman->desa_id,
    'tanggal_transaksi' => $pinjaman->tanggal_pinjaman,
    'uraian' => "...",
    'jenis_transaksi' => 'keluar',
    'jumlah' => $pinjaman->jumlah_pinjaman,
    'pinjaman_id' => $pinjaman->id,
    // ❌ TIDAK ADA: akun_kas_id
    // ❌ TIDAK ADA: akun_lawan_id
]);
```

### **2. TransaksiKas tidak auto-create Jurnal:**
- TransaksiKas yang dibuat dari Pinjaman/Angsuran tidak punya `akun_kas_id` dan `akun_lawan_id`
- Jadi tidak bisa auto-create jurnal (karena tidak tahu akun apa yang digunakan)

### **3. Belum ada seeder:**
- Tidak ada seeder untuk membuat data pinjaman dan angsuran untuk testing

---

## ✅ **SOLUSI YANG DIPERLUKAN**

### **1. Perbaiki Pinjaman::boot() untuk auto-create Jurnal:**
```php
static::created(function (Pinjaman $pinjaman) {
    // 1. Get akun
    $akunKas = Akun::where('desa_id', $pinjaman->desa_id)
        ->where('nama_akun', 'Kas')
        ->first();
    
    $akunPiutang = Akun::where('desa_id', $pinjaman->desa_id)
        ->where('nama_akun', 'Piutang Pinjaman Anggota')
        ->first();
    
    // 2. Create TransaksiKas
    $transaksiKas = TransaksiKas::create([
        'desa_id' => $pinjaman->desa_id,
        'tanggal_transaksi' => $pinjaman->tanggal_pinjaman,
        'uraian' => "Pencairan Pinjaman - {$pinjaman->nomor_pinjaman}",
        'jenis_transaksi' => 'keluar',
        'akun_kas_id' => $akunKas->id,
        'akun_lawan_id' => $akunPiutang->id,
        'jumlah' => $pinjaman->jumlah_pinjaman,
        'pinjaman_id' => $pinjaman->id,
    ]);
    
    // 3. Auto-create Jurnal
    $accountingService = app(AccountingService::class);
    $accountingService->createJurnal([
        'desa_id' => $pinjaman->desa_id,
        'tanggal_transaksi' => $pinjaman->tanggal_pinjaman,
        'jenis_jurnal' => 'kas_harian',
        'keterangan' => "Pencairan Pinjaman - {$pinjaman->nomor_pinjaman}",
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
```

### **2. Perbaiki AngsuranPinjaman::boot() untuk auto-create Jurnal:**
```php
static::created(function (AngsuranPinjaman $angsuran) {
    $pinjaman = $angsuran->pinjaman;
    
    // 1. Get akun
    $akunKas = Akun::where('desa_id', $pinjaman->desa_id)
        ->where('nama_akun', 'Kas')
        ->first();
    
    $akunPiutang = Akun::where('desa_id', $pinjaman->desa_id)
        ->where('nama_akun', 'Piutang Pinjaman Anggota')
        ->first();
    
    $akunPendapatanJasa = Akun::where('desa_id', $pinjaman->desa_id)
        ->where('nama_akun', 'like', '%Pendapatan Jasa Pinjaman%')
        ->first();
    
    // 2. Create TransaksiKas
    $transaksiKas = TransaksiKas::create([
        'desa_id' => $pinjaman->desa_id,
        'tanggal_transaksi' => $angsuran->tanggal_bayar,
        'uraian' => "Pembayaran Angsuran ke-{$angsuran->angsuran_ke}",
        'jenis_transaksi' => 'masuk',
        'akun_kas_id' => $akunKas->id,
        'akun_lawan_id' => $akunPiutang->id, // Default, akan di-override di jurnal
        'jumlah' => $angsuran->total_dibayar,
        'angsuran_pinjaman_id' => $angsuran->id,
    ]);
    
    // 3. Auto-create Jurnal (multi-account)
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
    
    $accountingService->createJurnal([
        'desa_id' => $pinjaman->desa_id,
        'tanggal_transaksi' => $angsuran->tanggal_bayar,
        'jenis_jurnal' => 'kas_harian',
        'keterangan' => "Pembayaran Angsuran ke-{$angsuran->angsuran_ke}",
        'status' => 'posted',
        'transaksi_kas_id' => $transaksiKas->id,
        'angsuran_pinjaman_id' => $angsuran->id,
        'details' => $details,
    ]);
});
```

### **3. Buat Seeder untuk Pinjaman & Angsuran:**
- Seeder untuk membuat pinjaman dan angsuran untuk testing
- Terintegrasi dengan data anggota yang sudah ada
- Periode Desember 2025 dan Januari 2026

---

## 📋 **RINGKASAN**

| Aspek | Status | Action Required |
|-------|--------|-----------------|
| Model Pinjaman & Angsuran | ✅ Ada | - |
| Relasi ke TransaksiKas | ✅ Ada | - |
| Relasi ke Jurnal | ✅ Ada | - |
| Auto-create TransaksiKas | ⚠️ Partial | Perlu tambah akun |
| Auto-create Jurnal | ❌ Tidak ada | **PERLU IMPLEMENTASI** |
| Seeder | ❌ Tidak ada | **PERLU BUAT** |
| Integrasi Akuntansi | ❌ Tidak lengkap | **PERLU PERBAIKI** |

---

## 🚀 **PRIORITY**

1. **HIGH**: Perbaiki integrasi Pinjaman & Angsuran dengan akuntansi
2. **HIGH**: Buat seeder untuk Pinjaman & Angsuran
3. **MEDIUM**: Pastikan semua transaksi masuk ke jurnal
4. **LOW**: Testing dan validasi
