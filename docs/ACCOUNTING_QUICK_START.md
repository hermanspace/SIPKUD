# 🚀 QUICK START GUIDE - Sistem Akuntansi Double Entry SIPKUD

## ⚡ Setup Cepat (5 Menit)

### 1️⃣ Jalankan Migration
```bash
php artisan migrate
```

### 2️⃣ Jalankan Seeder
```bash
php artisan db:seed --class=AkunSeeder
php artisan db:seed --class=UnitUsahaSeeder
```

### 3️⃣ Clear Cache
```bash
php artisan optimize:clear
```

✅ **Selesai!** Sistem siap digunakan.

---

## 📋 ALUR KERJA HARIAN

### **Skenario 1: Kas Masuk (Pendapatan Bunga)**

1. Buka menu **Kas Harian** → **Tambah Transaksi**
2. Isi form:
   - Tanggal: 23/01/2025
   - Jenis: **Masuk**
   - Akun Kas: **Kas** (1-1000)
   - Akun Lawan: **Pendapatan Jasa Pinjaman** (4-1000)
   - Jumlah: 1.000.000
   - Uraian: "Pendapatan bunga pinjaman Januari 2025"
3. Klik **Simpan**

**Hasil**: Jurnal otomatis dibuat
```
Debit: Kas                        Rp 1.000.000
Kredit: Pendapatan Jasa Pinjaman  Rp 1.000.000
```

---

### **Skenario 2: Kas Keluar (Bayar Gaji)**

1. Buka menu **Kas Harian** → **Tambah Transaksi**
2. Isi form:
   - Tanggal: 23/01/2025
   - Jenis: **Keluar**
   - Akun Kas: **Kas** (1-1000)
   - Akun Lawan: **Beban Gaji dan Upah** (5-1000)
   - Jumlah: 3.000.000
   - Uraian: "Pembayaran gaji karyawan Januari 2025"
3. Klik **Simpan**

**Hasil**: Jurnal otomatis dibuat
```
Debit: Beban Gaji dan Upah  Rp 3.000.000
Kredit: Kas                 Rp 3.000.000
```

---

### **Skenario 3: Transaksi Non-Kas (Penyusutan)**

1. Buka menu **Buku Memorial** → **Tambah Jurnal**
2. Isi form:
   - Tanggal: 31/01/2025
   - Keterangan: "Penyusutan peralatan kantor bulan Januari 2025"
3. Tambah baris jurnal:
   - **Baris 1**: Akun = Beban Penyusutan Peralatan (5-5000), Posisi = Debit, Jumlah = 500.000
   - **Baris 2**: Akun = Akumulasi Penyusutan Peralatan (1-1310), Posisi = Kredit, Jumlah = 500.000
4. Klik **Simpan**

**Hasil**: Jurnal memorial tersimpan
```
Debit: Beban Penyusutan Peralatan       Rp 500.000
Kredit: Akumulasi Penyusutan Peralatan  Rp 500.000
```

---

## 📊 MELIHAT LAPORAN

### **Neraca Saldo**
1. Buka menu **Laporan** → **Neraca Saldo**
2. Pilih bulan & tahun
3. (Optional) Pilih unit usaha
4. Klik **Tampilkan**

**Output**: Daftar semua akun dengan saldo debit/kredit

---

### **Laba Rugi**
1. Buka menu **Laporan** → **Laba Rugi**
2. Pilih bulan & tahun
3. (Optional) Pilih unit usaha
4. Klik **Tampilkan**

**Output**: 
- Total Pendapatan
- Total Beban
- **Laba/Rugi Bersih**

---

### **Neraca**
1. Buka menu **Laporan** → **Neraca**
2. Pilih tanggal (posisi neraca)
3. (Optional) Pilih unit usaha
4. Klik **Tampilkan**

**Output**:
- Total Aset
- Total Kewajiban
- Total Ekuitas
- **Validasi**: Aset = Kewajiban + Ekuitas

---

## 🎯 TIPS & TRIK

### ✅ **DO's**
- Selalu cek saldo kas sebelum input transaksi
- Gunakan keterangan yang jelas dan deskriptif
- Review neraca saldo setiap akhir bulan
- Backup database secara berkala

### ❌ **DON'Ts**
- Jangan edit jurnal yang sudah posted
- Jangan hapus transaksi yang sudah masuk laporan
- Jangan input transaksi dengan tanggal mundur (backdate) tanpa alasan jelas

---

## 🔍 VALIDASI CEPAT

### **Cek Balance Jurnal**
Setiap jurnal harus balance (debit = kredit). Sistem otomatis validasi.

### **Cek Neraca Saldo**
Total Debit = Total Kredit

### **Cek Neraca**
Aset = Kewajiban + Ekuitas

---

## 🆘 TROUBLESHOOTING CEPAT

| Problem | Solusi |
|---------|--------|
| Akun tidak muncul | Jalankan `AkunSeeder` |
| Unit usaha kosong | Jalankan `UnitUsahaSeeder` |
| Jurnal tidak balance | Periksa perhitungan jumlah |
| Tidak bisa edit jurnal | Jurnal posted tidak bisa diedit |

---

## 📞 BUTUH BANTUAN?

Baca dokumentasi lengkap di: `ACCOUNTING_SYSTEM_DOCUMENTATION.md`

---

**Happy Accounting! 🎉**
