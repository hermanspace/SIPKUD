# 📋 Testing Data Seeder

## 🎯 **TUJUAN**

Seeder ini membuat data lengkap untuk testing sistem akuntansi BUM Desa, khususnya untuk **Desa Kelapapati** dengan periode **Desember 2025** dan **Januari 2026**.

---

## 📊 **DATA YANG DIBUAT**

### 1. **User untuk Testing**
- **Email**: `admin.kelapapati@test.com`
- **Password**: `password`
- **Role**: Admin Desa
- **Desa**: Kelapapati

### 2. **Kelompok (4 kelompok)**
- Kelompok Tani Sejahtera
- Kelompok Nelayan Mandiri
- Kelompok Ibu PKK
- Kelompok Pemuda

### 3. **Anggota (10 anggota)**
- 10 anggota dengan data lengkap (NIK, alamat, nomor HP, jenis kelamin)
- Terhubung dengan kelompok

### 4. **Akun (COA) - 14 akun**
- **ASET**: Kas, Bank, Piutang Pinjaman Anggota, Penyusutan Aset
- **KEWAJIBAN**: Hutang Usaha, Hutang Bank
- **EKUITAS**: Modal, Laba Ditahan
- **PENDAPATAN**: Pendapatan Simpanan, Pendapatan Jasa Pinjaman
- **BEBAN**: Beban Operasional, Beban Gaji, Beban Administrasi, Beban Penyusutan

### 5. **Unit Usaha (2 unit)**
- Unit Simpan Pinjam (USP)
- Unit Usaha Umum (UMUM)

### 6. **Transaksi Kas Desember 2025 (10 transaksi)**
- **5 Kas Masuk**: Total Rp 16.500.000
  - Pendapatan simpanan anggota
  - Pendapatan jasa pinjaman
- **5 Kas Keluar**: Total Rp 7.500.000
  - Beban operasional
  - Beban gaji karyawan
  - Beban administrasi

### 7. **Transaksi Kas Januari 2026 (10 transaksi)**
- **5 Kas Masuk**: Total Rp 20.000.000
  - Pendapatan simpanan anggota
  - Pendapatan jasa pinjaman
- **5 Kas Keluar**: Total Rp 8.700.000
  - Beban operasional
  - Beban gaji karyawan
  - Beban administrasi

### 8. **Jurnal Memorial Desember 2025 (1 jurnal)**
- Penyusutan aset: Rp 500.000
- Debit: Beban Penyusutan
- Kredit: Penyusutan Aset

### 9. **Jurnal Memorial Januari 2026 (1 jurnal)**
- Penyusutan aset: Rp 500.000
- Debit: Beban Penyusutan
- Kredit: Penyusutan Aset

### 10. **Neraca Saldo**
- **Desember 2025**: Diposting otomatis
- **Januari 2026**: Diposting otomatis

---

## 🚀 **CARA MENGGUNAKAN**

### **1. Pastikan Database Seeder Dasar Sudah Dijalankan**

```bash
php artisan db:seed --class=KecamatanSeeder
php artisan db:seed --class=DesaSeeder
```

### **2. Jalankan Testing Data Seeder**

```bash
php artisan db:seed --class=TestingDataSeeder
```

### **3. Atau Jalankan Semua Seeder**

```bash
php artisan migrate:fresh --seed
# Lalu jalankan TestingDataSeeder
php artisan db:seed --class=TestingDataSeeder
```

---

## 📋 **OUTPUT YANG DIHARAPKAN**

```
✓ Menggunakan Desa: Desa Kelapapati (ID: X)
✓ User Admin Desa dibuat: admin.kelapapati@test.com (password: password)
✓ Kelompok berhasil dibuat
✓ Anggota berhasil dibuat (10 anggota)
✓ Akun (COA) berhasil dibuat
✓ Unit Usaha berhasil dibuat
✓ Transaksi Kas Desember 2025 berhasil dibuat (10 transaksi)
✓ Transaksi Kas Januari 2026 berhasil dibuat (10 transaksi)
✓ Jurnal Memorial Desember 2025 berhasil dibuat (1 jurnal)
✓ Jurnal Memorial Januari 2026 berhasil dibuat (1 jurnal)
✓ Neraca Saldo Desember 2025 berhasil diposting
✓ Neraca Saldo Januari 2026 berhasil diposting
✓ Testing data berhasil dibuat untuk Desa Kelapapati!
```

---

## 🔍 **VERIFIKASI DATA**

### **1. Cek User**
```sql
SELECT * FROM users WHERE email = 'admin.kelapapati@test.com';
```

### **2. Cek Anggota**
```sql
SELECT COUNT(*) FROM anggota WHERE desa_id = (SELECT id FROM desa WHERE nama_desa = 'Desa Kelapapati');
-- Expected: 10
```

### **3. Cek Transaksi Kas**
```sql
SELECT COUNT(*) FROM transaksi_kas 
WHERE desa_id = (SELECT id FROM desa WHERE nama_desa = 'Desa Kelapapati')
  AND tanggal_transaksi >= '2025-12-01' 
  AND tanggal_transaksi <= '2026-01-31';
-- Expected: 20
```

### **4. Cek Jurnal**
```sql
SELECT COUNT(*) FROM jurnal 
WHERE desa_id = (SELECT id FROM desa WHERE nama_desa = 'Desa Kelapapati')
  AND jenis_jurnal = 'umum';
-- Expected: 2
```

### **5. Cek Neraca Saldo**
```sql
SELECT COUNT(*) FROM neraca_saldo 
WHERE desa_id = (SELECT id FROM desa WHERE nama_desa = 'Desa Kelapapati')
  AND periode IN ('2025-12', '2026-01');
-- Expected: > 0 (tergantung jumlah akun)
```

---

## 📊 **RINGKASAN DATA**

| Kategori | Jumlah | Periode |
|----------|--------|----------|
| User | 1 | - |
| Kelompok | 4 | - |
| Anggota | 10 | - |
| Akun (COA) | 14 | - |
| Unit Usaha | 2 | - |
| Transaksi Kas | 20 | Des 2025 & Jan 2026 |
| Jurnal Memorial | 2 | Des 2025 & Jan 2026 |
| Neraca Saldo | 2 periode | Des 2025 & Jan 2026 |

---

## ⚠️ **CATATAN PENTING**

1. **Desa Kelapapati harus sudah ada** di database (dari `DesaSeeder`)
2. **Semua transaksi sudah balance** (debit = kredit)
3. **Jurnal sudah di-post** ke neraca saldo
4. **Data konsisten** untuk testing laporan keuangan
5. **Password default**: `password` (ubah di production!)

---

## 🔧 **TROUBLESHOOTING**

### **Q: Error "Desa Kelapapati tidak ditemukan"**
**A:** Jalankan `DesaSeeder` terlebih dahulu:
```bash
php artisan db:seed --class=DesaSeeder
```

### **Q: Error "Akun tidak lengkap"**
**A:** Pastikan `AkunSeeder` sudah dijalankan atau seeder akan membuat akun otomatis.

### **Q: Transaksi tidak muncul di jurnal**
**A:** Transaksi kas akan otomatis membuat jurnal via model event. Pastikan `AccountingService` sudah terintegrasi.

### **Q: Neraca Saldo kosong**
**A:** Pastikan method `postToLedger()` berhasil dijalankan. Cek log untuk error.

---

## ✅ **KESIMPULAN**

Seeder ini menyediakan data lengkap untuk testing:
- ✅ Master data (User, Kelompok, Anggota, Akun, Unit Usaha)
- ✅ Transaksi kas (Desember 2025 & Januari 2026)
- ✅ Jurnal memorial (Desember 2025 & Januari 2026)
- ✅ Neraca saldo (sudah diposting)

**Siap untuk testing sistem akuntansi!** 🎉
