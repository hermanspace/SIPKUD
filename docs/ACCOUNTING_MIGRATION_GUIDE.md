# 🔄 MIGRATION GUIDE - Upgrade ke Sistem Akuntansi Double Entry

## 📋 OVERVIEW

Guide ini menjelaskan langkah-langkah untuk mengupgrade sistem SIPKUD yang sudah berjalan ke sistem akuntansi double entry.

---

## ⚠️ PERSIAPAN

### **1. Backup Database**
```bash
# Backup database sebelum migration
mysqldump -u username -p database_name > backup_before_accounting_$(date +%Y%m%d_%H%M%S).sql
```

### **2. Backup Files**
```bash
# Backup folder aplikasi
tar -czf sipkud_backup_$(date +%Y%m%d_%H%M%S).tar.gz /path/to/sipkud
```

### **3. Pastikan Environment**
- PHP >= 8.1
- MySQL >= 8.0
- Laravel 11
- Composer updated

---

## 🚀 LANGKAH MIGRASI

### **STEP 1: Pull Latest Code**
```bash
cd /path/to/sipkud
git pull origin main  # atau branch yang sesuai
```

### **STEP 2: Install Dependencies**
```bash
composer install
npm install
npm run build
```

### **STEP 3: Jalankan Migration**
```bash
php artisan migrate
```

**Migration yang akan dijalankan**:
1. `2025_01_23_100000_create_unit_usaha_table.php`
2. `2025_01_23_100001_create_jurnal_table.php`
3. `2025_01_23_100002_create_jurnal_detail_table.php`
4. `2025_01_23_100003_add_accounting_fields_to_transaksi_kas.php`

### **STEP 4: Jalankan Seeder**
```bash
# Seeder untuk Chart of Accounts
php artisan db:seed --class=AkunSeeder

# Seeder untuk Unit Usaha
php artisan db:seed --class=UnitUsahaSeeder
```

### **STEP 5: Clear Cache**
```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### **STEP 6: Recompile Assets**
```bash
npm run build
```

---

## 🔄 MIGRASI DATA EXISTING

### **1. Migrasi Transaksi Kas ke Jurnal**

Jika sudah ada data transaksi kas, perlu dibuat jurnal untuk setiap transaksi.

**Script SQL**:
```sql
-- Pastikan akun kas sudah ada (dari AkunSeeder)
-- Misal: akun_kas_id = 1 (Kas)

-- Update transaksi kas dengan akun default
UPDATE transaksi_kas 
SET akun_kas_id = 1,  -- ID akun Kas
    akun_lawan_id = CASE 
        WHEN jenis_transaksi = 'masuk' THEN 23  -- ID akun Pendapatan Jasa Pinjaman
        WHEN jenis_transaksi = 'keluar' THEN 28  -- ID akun Beban Lain-lain
    END
WHERE akun_kas_id IS NULL;

-- Update unit_usaha_id (jika ada unit usaha default)
UPDATE transaksi_kas 
SET unit_usaha_id = (
    SELECT id FROM unit_usaha 
    WHERE kode_unit = 'USP' 
    AND desa_id = transaksi_kas.desa_id 
    LIMIT 1
)
WHERE unit_usaha_id IS NULL;
```

**Script PHP** (via Artisan Command):
```php
// Buat command: php artisan make:command MigrateKasToJurnal

use App\Models\TransaksiKas;
use App\Services\AccountingService;

$accountingService = app(AccountingService::class);

TransaksiKas::whereNull('jurnal_id')
    ->whereIn('jenis_transaksi', ['masuk', 'keluar'])
    ->chunk(100, function ($transaksiList) use ($accountingService) {
        foreach ($transaksiList as $transaksi) {
            // Skip jika akun belum diset
            if (!$transaksi->akun_kas_id || !$transaksi->akun_lawan_id) {
                continue;
            }
            
            $details = [];
            
            if ($transaksi->jenis_transaksi === 'masuk') {
                $details = [
                    [
                        'akun_id' => $transaksi->akun_kas_id,
                        'posisi' => 'debit',
                        'jumlah' => $transaksi->jumlah,
                        'keterangan' => $transaksi->uraian,
                    ],
                    [
                        'akun_id' => $transaksi->akun_lawan_id,
                        'posisi' => 'kredit',
                        'jumlah' => $transaksi->jumlah,
                        'keterangan' => $transaksi->uraian,
                    ],
                ];
            } else {
                $details = [
                    [
                        'akun_id' => $transaksi->akun_lawan_id,
                        'posisi' => 'debit',
                        'jumlah' => $transaksi->jumlah,
                        'keterangan' => $transaksi->uraian,
                    ],
                    [
                        'akun_id' => $transaksi->akun_kas_id,
                        'posisi' => 'kredit',
                        'jumlah' => $transaksi->jumlah,
                        'keterangan' => $transaksi->uraian,
                    ],
                ];
            }
            
            try {
                $accountingService->createJurnal([
                    'desa_id' => $transaksi->desa_id,
                    'unit_usaha_id' => $transaksi->unit_usaha_id,
                    'tanggal_transaksi' => $transaksi->tanggal_transaksi,
                    'jenis_jurnal' => 'kas_harian',
                    'keterangan' => $transaksi->uraian,
                    'status' => 'posted',
                    'transaksi_kas_id' => $transaksi->id,
                    'details' => $details,
                ]);
                
                $this->info("✓ Jurnal created for TransaksiKas ID: {$transaksi->id}");
            } catch (\Exception $e) {
                $this->error("✗ Failed for TransaksiKas ID: {$transaksi->id} - {$e->getMessage()}");
            }
        }
    });
```

### **2. Mapping Akun untuk Transaksi Existing**

Buat mapping table untuk memudahkan migrasi:

```sql
-- Mapping akun untuk transaksi kas masuk
CREATE TEMPORARY TABLE akun_mapping (
    jenis_transaksi VARCHAR(50),
    keyword VARCHAR(100),
    akun_id BIGINT
);

INSERT INTO akun_mapping VALUES
('masuk', 'bunga', (SELECT id FROM akun WHERE kode_akun = '4-1000' LIMIT 1)),
('masuk', 'administrasi', (SELECT id FROM akun WHERE kode_akun = '4-1100' LIMIT 1)),
('masuk', 'denda', (SELECT id FROM akun WHERE kode_akun = '4-1200' LIMIT 1)),
('keluar', 'gaji', (SELECT id FROM akun WHERE kode_akun = '5-1000' LIMIT 1)),
('keluar', 'listrik', (SELECT id FROM akun WHERE kode_akun = '5-2000' LIMIT 1)),
('keluar', 'atk', (SELECT id FROM akun WHERE kode_akun = '5-3000' LIMIT 1));

-- Update berdasarkan keyword di uraian
UPDATE transaksi_kas tk
JOIN akun_mapping am ON tk.jenis_transaksi = am.jenis_transaksi
SET tk.akun_lawan_id = am.akun_id
WHERE tk.akun_lawan_id IS NULL
  AND LOWER(tk.uraian) LIKE CONCAT('%', am.keyword, '%');
```

---

## ✅ VALIDASI SETELAH MIGRASI

### **1. Cek Tabel Baru**
```sql
-- Cek tabel unit_usaha
SELECT * FROM unit_usaha;

-- Cek tabel jurnal
SELECT * FROM jurnal LIMIT 10;

-- Cek tabel jurnal_detail
SELECT * FROM jurnal_detail LIMIT 10;

-- Cek update transaksi_kas
SELECT id, desa_id, unit_usaha_id, akun_kas_id, akun_lawan_id 
FROM transaksi_kas 
LIMIT 10;
```

### **2. Cek Akun Standar**
```sql
-- Harus ada 45 akun per desa
SELECT desa_id, COUNT(*) as jumlah_akun 
FROM akun 
GROUP BY desa_id;
```

### **3. Cek Unit Usaha**
```sql
-- Harus ada 2 unit usaha per desa (USP dan UMUM)
SELECT desa_id, COUNT(*) as jumlah_unit 
FROM unit_usaha 
GROUP BY desa_id;
```

### **4. Cek Balance Jurnal**
```sql
-- Semua jurnal harus balance
SELECT 
    id, 
    nomor_jurnal, 
    total_debit, 
    total_kredit,
    total_debit - total_kredit as selisih
FROM jurnal
WHERE total_debit != total_kredit;

-- Harus return 0 rows
```

### **5. Test Akses Menu**
- [ ] Buka menu **Kas Harian**
- [ ] Buka menu **Buku Memorial**
- [ ] Buka menu **Laporan > Neraca Saldo**
- [ ] Buka menu **Laporan > Laba Rugi**
- [ ] Buka menu **Laporan > Neraca**
- [ ] Buka menu **Master Data > Unit Usaha**

---

## 🔧 TROUBLESHOOTING

### **Problem: Migration Failed**
```
SQLSTATE[42S01]: Base table or view already exists
```
**Solusi**:
```bash
# Rollback migration
php artisan migrate:rollback --step=4

# Jalankan ulang
php artisan migrate
```

### **Problem: Seeder Failed**
```
Integrity constraint violation: Duplicate entry
```
**Solusi**:
```bash
# Hapus data akun dan unit usaha
DELETE FROM akun WHERE id > 0;
DELETE FROM unit_usaha WHERE id > 0;

# Jalankan ulang seeder
php artisan db:seed --class=AkunSeeder
php artisan db:seed --class=UnitUsahaSeeder
```

### **Problem: Transaksi Kas Tidak Punya Akun**
```sql
-- Cek transaksi kas yang belum punya akun
SELECT id, tanggal_transaksi, uraian, jenis_transaksi
FROM transaksi_kas
WHERE akun_kas_id IS NULL OR akun_lawan_id IS NULL;

-- Set akun default
UPDATE transaksi_kas 
SET akun_kas_id = (SELECT id FROM akun WHERE kode_akun = '1-1000' LIMIT 1),
    akun_lawan_id = (SELECT id FROM akun WHERE kode_akun = '4-2000' LIMIT 1)
WHERE akun_kas_id IS NULL;
```

---

## 📊 ROLLBACK PLAN

Jika terjadi masalah dan perlu rollback:

### **1. Restore Database**
```bash
mysql -u username -p database_name < backup_before_accounting_YYYYMMDD_HHMMSS.sql
```

### **2. Rollback Migration**
```bash
php artisan migrate:rollback --step=4
```

### **3. Restore Files**
```bash
tar -xzf sipkud_backup_YYYYMMDD_HHMMSS.tar.gz -C /path/to/restore
```

---

## 📝 CHECKLIST MIGRASI

### **Pre-Migration**
- [ ] Backup database
- [ ] Backup files
- [ ] Cek environment (PHP, MySQL, Laravel)
- [ ] Informasikan ke user (maintenance mode)

### **Migration**
- [ ] Pull latest code
- [ ] Install dependencies
- [ ] Jalankan migration
- [ ] Jalankan seeder
- [ ] Clear cache
- [ ] Recompile assets

### **Post-Migration**
- [ ] Validasi tabel baru
- [ ] Validasi data akun
- [ ] Validasi unit usaha
- [ ] Migrasi transaksi kas ke jurnal (jika ada)
- [ ] Test akses menu
- [ ] Test input transaksi baru
- [ ] Test laporan

### **User Training**
- [ ] Training untuk Admin Desa
- [ ] Dokumentasi user manual
- [ ] Demo sistem baru
- [ ] Q&A session

---

## 🎓 TRAINING CHECKLIST

### **Materi Training**
1. ✅ Konsep Double Entry Accounting
2. ✅ Dua Titik Input (Kas Harian & Memorial)
3. ✅ Cara Input Transaksi Kas
4. ✅ Cara Input Jurnal Memorial
5. ✅ Cara Melihat Laporan
6. ✅ Cara Manage Unit Usaha
7. ✅ Tips & Best Practices

### **Hands-On Practice**
- [ ] Input kas masuk
- [ ] Input kas keluar
- [ ] Input jurnal memorial
- [ ] Lihat neraca saldo
- [ ] Lihat laba rugi
- [ ] Lihat neraca
- [ ] Export PDF

---

## 📞 SUPPORT

Jika ada pertanyaan atau kendala saat migrasi:
1. Cek dokumentasi: `ACCOUNTING_SYSTEM_DOCUMENTATION.md`
2. Cek quick start: `ACCOUNTING_QUICK_START.md`
3. Cek SQL queries: `ACCOUNTING_SQL_QUERIES.sql`
4. Hubungi tim development

---

## ✅ KESIMPULAN

Setelah mengikuti guide ini, sistem SIPKUD Anda akan:
- ✅ Memiliki sistem akuntansi double entry yang lengkap
- ✅ Support multi unit usaha
- ✅ Memiliki laporan keuangan otomatis
- ✅ Terintegrasi dengan data existing
- ✅ Siap untuk produksi

---

**Good Luck! 🚀**

**© 2025 SIPKUD - Sistem Informasi Pelaporan Keuangan USP Desa**
