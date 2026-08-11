# 📚 DOKUMENTASI SISTEM AKUNTANSI DOUBLE ENTRY - SIPKUD

## 🎯 OVERVIEW

Sistem Informasi Pelaporan Keuangan USP Desa (SIPKUD) telah diupgrade dengan **sistem akuntansi double entry** yang lengkap dan production-ready untuk BUM Desa.

### Prinsip Dasar
- ✅ **Double Entry Accounting**: Setiap transaksi memiliki debit = kredit
- ✅ **Basis Kas + Memorial**: Dua titik input utama
- ✅ **Multi Unit Usaha**: Mendukung beberapa unit usaha dalam satu BUM Desa
- ✅ **Laporan Otomatis**: Semua laporan di-generate dari jurnal (read-only)
- ✅ **Integritas Data**: Validasi ketat dengan service layer

---

## 📊 STRUKTUR DATABASE

### 1. **unit_usaha**
Representasi unit usaha dalam BUM Desa (USP, UED-SP, dll)

| Field | Type | Keterangan |
|-------|------|------------|
| id | bigint | Primary key |
| desa_id | bigint | FK ke desa |
| kode_unit | varchar(20) | Kode unit (USP, UMUM, dll) |
| nama_unit | varchar(255) | Nama unit usaha |
| deskripsi | text | Deskripsi unit |
| status | enum | aktif/nonaktif |

**Unique Constraint**: `desa_id + kode_unit`

### 2. **jurnal**
Header jurnal untuk mencatat transaksi akuntansi

| Field | Type | Keterangan |
|-------|------|------------|
| id | bigint | Primary key |
| desa_id | bigint | FK ke desa |
| unit_usaha_id | bigint | FK ke unit_usaha (nullable) |
| nomor_jurnal | varchar | Auto-generate: JRN/YYYY/MM/XXXXX |
| tanggal_transaksi | date | Tanggal transaksi |
| jenis_jurnal | enum | kas_harian, memorial, penyesuaian, penutup |
| keterangan | text | Keterangan transaksi |
| total_debit | decimal(15,2) | Total debit |
| total_kredit | decimal(15,2) | Total kredit |
| status | enum | draft, posted, void |
| transaksi_kas_id | bigint | FK ke transaksi_kas (nullable) |

**Prinsip**: `total_debit = total_kredit` (always balanced)

### 3. **jurnal_detail**
Detail jurnal (baris debit/kredit)

| Field | Type | Keterangan |
|-------|------|------------|
| id | bigint | Primary key |
| jurnal_id | bigint | FK ke jurnal |
| akun_id | bigint | FK ke akun |
| posisi | enum | debit/kredit |
| jumlah | decimal(15,2) | Jumlah |
| keterangan | text | Keterangan (nullable) |

### 4. **transaksi_kas** (Updated)
Ditambahkan field untuk integrasi dengan jurnal

**Field Baru**:
- `unit_usaha_id`: FK ke unit_usaha
- `akun_kas_id`: Akun kas/bank yang digunakan
- `akun_lawan_id`: Akun lawan (pendapatan/biaya/dll)

### 5. **akun** (Existing)
Chart of Accounts dengan tipe: aset, kewajiban, ekuitas, pendapatan, beban

---

## 🔧 SERVICE LAYER

### **AccountingService**
Service utama untuk menangani operasi akuntansi

#### Methods:

**1. createJurnal(array $data): Jurnal**
Membuat jurnal baru dengan validasi double entry

```php
$accountingService->createJurnal([
    'desa_id' => 1,
    'unit_usaha_id' => 1,
    'tanggal_transaksi' => '2025-01-23',
    'jenis_jurnal' => 'memorial',
    'keterangan' => 'Pembelian perlengkapan kantor',
    'status' => 'posted',
    'details' => [
        [
            'akun_id' => 10, // Perlengkapan Kantor
            'posisi' => 'debit',
            'jumlah' => 500000,
            'keterangan' => 'Pembelian ATK'
        ],
        [
            'akun_id' => 1, // Kas
            'posisi' => 'kredit',
            'jumlah' => 500000,
            'keterangan' => 'Pembayaran tunai'
        ]
    ]
]);
```

**Validasi**:
- ✅ Minimal 2 baris (debit & kredit)
- ✅ Total debit = total kredit (menggunakan bccomp untuk akurasi)
- ✅ Akun harus aktif dan valid
- ✅ Transaksi atomik (DB transaction)

**2. updateJurnal(Jurnal $jurnal, array $data): Jurnal**
Update jurnal (hanya draft yang bisa diupdate)

**3. voidJurnal(Jurnal $jurnal): Jurnal**
Void jurnal (pembatalan)

**4. postJurnal(Jurnal $jurnal): Jurnal**
Post jurnal dari draft ke posted

**5. getNeracaSaldo(int $desaId, ?int $bulan, ?int $tahun, ?int $unitUsahaId): array**
Generate neraca saldo untuk periode tertentu

**Return**:
```php
[
    [
        'akun_id' => 1,
        'kode_akun' => '1-1000',
        'nama_akun' => 'Kas',
        'tipe_akun' => 'aset',
        'total_debit' => 10000000,
        'total_kredit' => 5000000,
        'saldo' => 5000000,
        'posisi_saldo' => 'debit'
    ],
    // ...
]
```

**6. getLabaRugi(int $desaId, int $bulan, int $tahun, ?int $unitUsahaId): array**
Generate laporan laba rugi

**Return**:
```php
[
    'pendapatan' => 15000000,
    'beban' => 8000000,
    'laba_rugi' => 7000000,
    'detail_pendapatan' => [...],
    'detail_beban' => [...]
]
```

**7. getNeraca(int $desaId, string $tanggal, ?int $unitUsahaId): array**
Generate neraca (balance sheet) pada tanggal tertentu

**Return**:
```php
[
    'aset' => 50000000,
    'kewajiban' => 20000000,
    'ekuitas' => 30000000,
    'detail_aset' => [...],
    'detail_kewajiban' => [...],
    'detail_ekuitas' => [...]
]
```

---

## 📝 DUA TITIK INPUT UTAMA

### 1. **KAS HARIAN** (Transaksi Kas)
**Route**: `/kas`

**Livewire Components**:
- `App\Livewire\Kas\Index` - List transaksi kas
- `App\Livewire\Kas\Create` - Tambah transaksi kas
- `App\Livewire\Kas\Edit` - Edit transaksi kas

**Flow**:
1. User input transaksi kas (masuk/keluar)
2. Pilih akun kas dan akun lawan
3. Sistem **otomatis** membuat jurnal:
   - **Kas Masuk**: Debit Kas, Kredit Akun Lawan
   - **Kas Keluar**: Debit Akun Lawan, Kredit Kas
4. Jurnal langsung di-post (status: posted)

**Field**:
- Tanggal transaksi
- Unit usaha (optional)
- Jenis transaksi (masuk/keluar)
- Akun kas (pilih dari akun kas/bank)
- Akun lawan (pilih dari semua akun)
- Jumlah
- Uraian

### 2. **BUKU MEMORIAL** (Transaksi Non-Kas)
**Route**: `/memorial`

**Livewire Components**:
- `App\Livewire\Memorial\Index` - List jurnal memorial
- `App\Livewire\Memorial\Create` - Tambah jurnal memorial
- `App\Livewire\Memorial\Edit` - Edit jurnal memorial (draft only)

**Flow**:
1. User input jurnal memorial (transaksi non-kas)
2. Input minimal 2 baris (debit & kredit)
3. Sistem validasi balance (debit = kredit)
4. Bisa disimpan sebagai draft atau langsung posted

**Contoh Transaksi Memorial**:
- Penyusutan aset
- Cadangan kerugian piutang
- Koreksi kesalahan pencatatan
- Jurnal penyesuaian akhir periode

---

## 📊 LAPORAN KEUANGAN (READ-ONLY)

Semua laporan di-generate otomatis dari data jurnal. **TIDAK ADA INPUT MANUAL**.

### 1. **Neraca Saldo**
**Route**: `/laporan/neraca-saldo`

**Component**: `App\Livewire\Laporan\NeracaSaldo`

**Filter**:
- Bulan & Tahun
- Unit Usaha

**Output**:
- Daftar akun dengan total debit, kredit, dan saldo
- Group by tipe akun (aset, kewajiban, ekuitas, pendapatan, beban)
- Total debit = total kredit (balanced)

### 2. **Laporan Laba Rugi**
**Route**: `/laporan/laba-rugi`

**Component**: `App\Livewire\Laporan\LabaRugi`

**Filter**:
- Bulan & Tahun (required)
- Unit Usaha

**Output**:
- Total Pendapatan
- Total Beban
- Laba/Rugi Bersih
- Detail per akun pendapatan dan beban

### 3. **Neraca (Balance Sheet)**
**Route**: `/laporan/neraca`

**Component**: `App\Livewire\Laporan\Neraca`

**Filter**:
- Tanggal (posisi neraca pada tanggal tertentu)
- Unit Usaha

**Output**:
- Total Aset
- Total Kewajiban
- Total Ekuitas
- Detail per akun
- **Validasi**: Aset = Kewajiban + Ekuitas

### 4. **Buku Kas** (Existing)
**Route**: `/laporan/buku-kas`

Laporan kas harian yang sudah ada sebelumnya, tetap berfungsi.

---

## 🏢 MASTER DATA

### **Unit Usaha**
**Route**: `/master-data/unit-usaha`

**Components**:
- `App\Livewire\MasterData\UnitUsaha\Index`
- `App\Livewire\MasterData\UnitUsaha\Create`
- `App\Livewire\MasterData\UnitUsaha\Edit`

**Contoh Unit Usaha**:
- USP (Unit Simpan Pinjam)
- UED-SP (Unit Ekonomi Desa - Simpan Pinjam)
- UMUM (Unit Usaha Umum)
- PERDAGANGAN
- JASA

### **Akun (COA)** (Existing)
**Route**: `/master-data/akun`

Chart of Accounts sudah ada, dengan seeder standar BUM Desa.

---

## 🌱 SEEDER

### **AkunSeeder**
Membuat Chart of Accounts standar untuk BUM Desa

**Struktur COA**:
- **1-xxxx**: ASET (Kas, Bank, Piutang, Peralatan, dll)
- **2-xxxx**: KEWAJIBAN (Simpanan, Utang, dll)
- **3-xxxx**: EKUITAS (Modal, Cadangan, SHU)
- **4-xxxx**: PENDAPATAN (Jasa Pinjaman, Administrasi, dll)
- **5-xxxx**: BEBAN (Gaji, Listrik, ATK, dll)

**Run**:
```bash
php artisan db:seed --class=AkunSeeder
```

### **UnitUsahaSeeder**
Membuat unit usaha standar (USP dan UMUM)

**Run**:
```bash
php artisan db:seed --class=UnitUsahaSeeder
```

---

## 🚀 INSTALASI & SETUP

### 1. **Jalankan Migration**
```bash
php artisan migrate
```

**Migrations**:
- `2025_01_23_100000_create_unit_usaha_table.php`
- `2025_01_23_100001_create_jurnal_table.php`
- `2025_01_23_100002_create_jurnal_detail_table.php`
- `2025_01_23_100003_add_accounting_fields_to_transaksi_kas.php`

### 2. **Jalankan Seeder**
```bash
php artisan db:seed --class=AkunSeeder
php artisan db:seed --class=UnitUsahaSeeder
```

### 3. **Clear Cache**
```bash
php artisan optimize:clear
```

---

## 🔐 AUTHORIZATION

Semua fitur akuntansi memerlukan role **Admin Desa**.

**Gate**:
- `admin_desa`: Akses penuh ke kas harian dan memorial
- `view_desa_data`: Akses ke laporan (Admin Desa, Admin Kecamatan, Super Admin)

---

## 📐 PRINSIP AKUNTANSI

### **Normal Balance**
| Tipe Akun | Normal Balance | Penambahan | Pengurangan |
|-----------|---------------|------------|-------------|
| Aset | Debit | Debit | Kredit |
| Kewajiban | Kredit | Kredit | Debit |
| Ekuitas | Kredit | Kredit | Debit |
| Pendapatan | Kredit | Kredit | Debit |
| Beban | Debit | Debit | Kredit |

### **Contoh Transaksi**

**1. Kas Masuk dari Bunga Pinjaman**
```
Debit: Kas                    Rp 1.000.000
Kredit: Pendapatan Bunga      Rp 1.000.000
```

**2. Kas Keluar untuk Gaji**
```
Debit: Beban Gaji             Rp 3.000.000
Kredit: Kas                   Rp 3.000.000
```

**3. Memorial: Penyusutan Peralatan**
```
Debit: Beban Penyusutan       Rp 500.000
Kredit: Akum. Penyusutan      Rp 500.000
```

**4. Pencairan Pinjaman ke Anggota**
```
Debit: Piutang Pinjaman       Rp 10.000.000
Kredit: Kas                   Rp 10.000.000
```

---

## 🧪 TESTING

### **Manual Testing Checklist**

**Kas Harian**:
- [ ] Tambah kas masuk → cek jurnal otomatis dibuat
- [ ] Tambah kas keluar → cek jurnal otomatis dibuat
- [ ] Edit transaksi kas → cek jurnal terupdate
- [ ] Hapus transaksi kas → cek jurnal terhapus

**Buku Memorial**:
- [ ] Tambah jurnal memorial → cek balance
- [ ] Simpan sebagai draft → cek status
- [ ] Post jurnal draft → cek status berubah
- [ ] Edit jurnal draft → cek bisa diedit
- [ ] Edit jurnal posted → cek tidak bisa diedit

**Laporan**:
- [ ] Neraca Saldo → cek total debit = kredit
- [ ] Laba Rugi → cek pendapatan - beban
- [ ] Neraca → cek aset = kewajiban + ekuitas
- [ ] Filter per unit usaha → cek data sesuai

**Validasi**:
- [ ] Input jurnal tidak balance → cek error
- [ ] Input dengan akun nonaktif → cek error
- [ ] Hapus akun yang digunakan → cek restricted

---

## 📈 BEST PRACTICES

### **DO's** ✅
1. Selalu gunakan `AccountingService` untuk operasi jurnal
2. Validasi balance sebelum post jurnal
3. Gunakan DB transaction untuk operasi kompleks
4. Gunakan bcmath untuk perhitungan decimal (akurasi)
5. Filter data berdasarkan `desa_id` (multi-tenancy)
6. Log semua perubahan dengan `created_by` dan `updated_by`

### **DON'Ts** ❌
1. Jangan input manual ke tabel jurnal/jurnal_detail
2. Jangan edit jurnal yang sudah posted
3. Jangan hapus akun yang sudah digunakan di jurnal
4. Jangan bypass validasi balance
5. Jangan hardcode akun_id (gunakan kode_akun)

---

## 🐛 TROUBLESHOOTING

### **Jurnal tidak balance**
```
Error: Jurnal tidak balance. Debit: 1000000.00, Kredit: 1000001.00
```
**Solusi**: Periksa perhitungan jumlah, pastikan tidak ada pembulatan yang salah.

### **Akun tidak ditemukan**
```
Error: Akun dengan ID xxx tidak ditemukan.
```
**Solusi**: Jalankan `AkunSeeder` atau buat akun manual.

### **Tidak bisa edit jurnal**
```
Error: Hanya jurnal dengan status draft yang dapat diubah.
```
**Solusi**: Jurnal posted tidak bisa diedit. Buat jurnal koreksi (memorial) jika perlu.

---

## 📞 SUPPORT

Untuk pertanyaan atau issue, silakan hubungi tim development.

---

**Version**: 1.0.0  
**Last Updated**: 23 Januari 2025  
**Framework**: Laravel 11  
**Database**: MySQL 8.0+

---

## 🎓 REFERENSI

- Standar Akuntansi Keuangan (SAK)
- Pedoman Akuntansi BUM Desa
- Laravel Best Practices
- Double Entry Bookkeeping Principles

---

**© 2025 SIPKUD - Sistem Informasi Pelaporan Keuangan USP Desa**
