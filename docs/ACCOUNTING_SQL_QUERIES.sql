-- =====================================================
-- ACCOUNTING SQL QUERIES - SIPKUD
-- Kumpulan query SQL yang berguna untuk analisis data
-- =====================================================

-- =====================================================
-- 1. NERACA SALDO PER PERIODE
-- =====================================================
SELECT 
    a.kode_akun,
    a.nama_akun,
    a.tipe_akun,
    SUM(CASE WHEN jd.posisi = 'debit' THEN jd.jumlah ELSE 0 END) as total_debit,
    SUM(CASE WHEN jd.posisi = 'kredit' THEN jd.jumlah ELSE 0 END) as total_kredit,
    SUM(CASE WHEN jd.posisi = 'debit' THEN jd.jumlah ELSE -jd.jumlah END) as saldo
FROM akun a
LEFT JOIN jurnal_detail jd ON a.id = jd.akun_id
LEFT JOIN jurnal j ON jd.jurnal_id = j.id
WHERE j.desa_id = 1  -- Ganti dengan desa_id yang sesuai
  AND j.status = 'posted'
  AND YEAR(j.tanggal_transaksi) = 2025
  AND MONTH(j.tanggal_transaksi) = 1
GROUP BY a.id, a.kode_akun, a.nama_akun, a.tipe_akun
ORDER BY a.kode_akun;

-- =====================================================
-- 2. LABA RUGI PER PERIODE
-- =====================================================
SELECT 
    'PENDAPATAN' as kategori,
    a.kode_akun,
    a.nama_akun,
    SUM(CASE WHEN jd.posisi = 'kredit' THEN jd.jumlah ELSE -jd.jumlah END) as jumlah
FROM akun a
LEFT JOIN jurnal_detail jd ON a.id = jd.akun_id
LEFT JOIN jurnal j ON jd.jurnal_id = j.id
WHERE j.desa_id = 1
  AND j.status = 'posted'
  AND a.tipe_akun = 'pendapatan'
  AND YEAR(j.tanggal_transaksi) = 2025
  AND MONTH(j.tanggal_transaksi) = 1
GROUP BY a.id, a.kode_akun, a.nama_akun

UNION ALL

SELECT 
    'BEBAN' as kategori,
    a.kode_akun,
    a.nama_akun,
    SUM(CASE WHEN jd.posisi = 'debit' THEN jd.jumlah ELSE -jd.jumlah END) as jumlah
FROM akun a
LEFT JOIN jurnal_detail jd ON a.id = jd.akun_id
LEFT JOIN jurnal j ON jd.jurnal_id = j.id
WHERE j.desa_id = 1
  AND j.status = 'posted'
  AND a.tipe_akun = 'beban'
  AND YEAR(j.tanggal_transaksi) = 2025
  AND MONTH(j.tanggal_transaksi) = 1
GROUP BY a.id, a.kode_akun, a.nama_akun
ORDER BY kategori DESC, kode_akun;

-- =====================================================
-- 3. NERACA (BALANCE SHEET) PADA TANGGAL TERTENTU
-- =====================================================
SELECT 
    a.tipe_akun,
    a.kode_akun,
    a.nama_akun,
    SUM(CASE WHEN jd.posisi = 'debit' THEN jd.jumlah ELSE -jd.jumlah END) as saldo
FROM akun a
LEFT JOIN jurnal_detail jd ON a.id = jd.akun_id
LEFT JOIN jurnal j ON jd.jurnal_id = j.id
WHERE j.desa_id = 1
  AND j.status = 'posted'
  AND j.tanggal_transaksi <= '2025-01-31'
  AND a.tipe_akun IN ('aset', 'kewajiban', 'ekuitas')
GROUP BY a.id, a.tipe_akun, a.kode_akun, a.nama_akun
ORDER BY a.tipe_akun, a.kode_akun;

-- =====================================================
-- 4. SUMMARY LABA RUGI (TOTAL SAJA)
-- =====================================================
SELECT 
    SUM(CASE WHEN a.tipe_akun = 'pendapatan' THEN 
        CASE WHEN jd.posisi = 'kredit' THEN jd.jumlah ELSE -jd.jumlah END 
    ELSE 0 END) as total_pendapatan,
    
    SUM(CASE WHEN a.tipe_akun = 'beban' THEN 
        CASE WHEN jd.posisi = 'debit' THEN jd.jumlah ELSE -jd.jumlah END 
    ELSE 0 END) as total_beban,
    
    SUM(CASE WHEN a.tipe_akun = 'pendapatan' THEN 
        CASE WHEN jd.posisi = 'kredit' THEN jd.jumlah ELSE -jd.jumlah END 
    ELSE 0 END) - 
    SUM(CASE WHEN a.tipe_akun = 'beban' THEN 
        CASE WHEN jd.posisi = 'debit' THEN jd.jumlah ELSE -jd.jumlah END 
    ELSE 0 END) as laba_rugi
FROM jurnal_detail jd
JOIN jurnal j ON jd.jurnal_id = j.id
JOIN akun a ON jd.akun_id = a.id
WHERE j.desa_id = 1
  AND j.status = 'posted'
  AND YEAR(j.tanggal_transaksi) = 2025
  AND MONTH(j.tanggal_transaksi) = 1;

-- =====================================================
-- 5. SUMMARY NERACA (TOTAL SAJA)
-- =====================================================
SELECT 
    SUM(CASE WHEN a.tipe_akun = 'aset' THEN 
        CASE WHEN jd.posisi = 'debit' THEN jd.jumlah ELSE -jd.jumlah END 
    ELSE 0 END) as total_aset,
    
    SUM(CASE WHEN a.tipe_akun = 'kewajiban' THEN 
        CASE WHEN jd.posisi = 'kredit' THEN jd.jumlah ELSE -jd.jumlah END 
    ELSE 0 END) as total_kewajiban,
    
    SUM(CASE WHEN a.tipe_akun = 'ekuitas' THEN 
        CASE WHEN jd.posisi = 'kredit' THEN jd.jumlah ELSE -jd.jumlah END 
    ELSE 0 END) as total_ekuitas
FROM jurnal_detail jd
JOIN jurnal j ON jd.jurnal_id = j.id
JOIN akun a ON jd.akun_id = a.id
WHERE j.desa_id = 1
  AND j.status = 'posted'
  AND j.tanggal_transaksi <= '2025-01-31';

-- =====================================================
-- 6. DAFTAR JURNAL PER PERIODE
-- =====================================================
SELECT 
    j.nomor_jurnal,
    j.tanggal_transaksi,
    j.jenis_jurnal,
    j.keterangan,
    j.total_debit,
    j.total_kredit,
    j.status,
    u.nama_unit as unit_usaha,
    us.nama as dibuat_oleh
FROM jurnal j
LEFT JOIN unit_usaha u ON j.unit_usaha_id = u.id
LEFT JOIN users us ON j.created_by = us.id
WHERE j.desa_id = 1
  AND YEAR(j.tanggal_transaksi) = 2025
  AND MONTH(j.tanggal_transaksi) = 1
ORDER BY j.tanggal_transaksi DESC, j.id DESC;

-- =====================================================
-- 7. DETAIL JURNAL (HEADER + DETAIL)
-- =====================================================
SELECT 
    j.nomor_jurnal,
    j.tanggal_transaksi,
    j.keterangan as keterangan_jurnal,
    a.kode_akun,
    a.nama_akun,
    jd.posisi,
    jd.jumlah,
    jd.keterangan as keterangan_detail
FROM jurnal j
JOIN jurnal_detail jd ON j.id = jd.jurnal_id
JOIN akun a ON jd.akun_id = a.id
WHERE j.nomor_jurnal = 'JRN/2025/01/00001'  -- Ganti dengan nomor jurnal yang dicari
ORDER BY jd.id;

-- =====================================================
-- 8. VALIDASI BALANCE SEMUA JURNAL
-- =====================================================
SELECT 
    j.id,
    j.nomor_jurnal,
    j.tanggal_transaksi,
    j.total_debit,
    j.total_kredit,
    j.total_debit - j.total_kredit as selisih,
    CASE 
        WHEN j.total_debit = j.total_kredit THEN 'BALANCE'
        ELSE 'NOT BALANCE'
    END as status_balance
FROM jurnal j
WHERE j.desa_id = 1
  AND j.status = 'posted'
HAVING selisih != 0;  -- Hanya tampilkan yang tidak balance

-- =====================================================
-- 9. TRANSAKSI KAS TERBANYAK PER AKUN
-- =====================================================
SELECT 
    a.kode_akun,
    a.nama_akun,
    COUNT(*) as jumlah_transaksi,
    SUM(tk.jumlah) as total_nilai
FROM transaksi_kas tk
JOIN akun a ON tk.akun_lawan_id = a.id
WHERE tk.desa_id = 1
  AND YEAR(tk.tanggal_transaksi) = 2025
  AND MONTH(tk.tanggal_transaksi) = 1
GROUP BY a.id, a.kode_akun, a.nama_akun
ORDER BY total_nilai DESC
LIMIT 10;

-- =====================================================
-- 10. SALDO KAS REAL-TIME
-- =====================================================
SELECT 
    a.kode_akun,
    a.nama_akun,
    SUM(CASE WHEN jd.posisi = 'debit' THEN jd.jumlah ELSE -jd.jumlah END) as saldo_kas
FROM akun a
LEFT JOIN jurnal_detail jd ON a.id = jd.akun_id
LEFT JOIN jurnal j ON jd.jurnal_id = j.id
WHERE a.desa_id = 1
  AND a.tipe_akun = 'aset'
  AND (a.kode_akun LIKE '1-10%' OR a.nama_akun LIKE '%kas%' OR a.nama_akun LIKE '%bank%')
  AND j.status = 'posted'
GROUP BY a.id, a.kode_akun, a.nama_akun
ORDER BY a.kode_akun;

-- =====================================================
-- 11. PERBANDINGAN LABA RUGI BULANAN (YTD)
-- =====================================================
SELECT 
    MONTH(j.tanggal_transaksi) as bulan,
    SUM(CASE WHEN a.tipe_akun = 'pendapatan' THEN 
        CASE WHEN jd.posisi = 'kredit' THEN jd.jumlah ELSE -jd.jumlah END 
    ELSE 0 END) as pendapatan,
    
    SUM(CASE WHEN a.tipe_akun = 'beban' THEN 
        CASE WHEN jd.posisi = 'debit' THEN jd.jumlah ELSE -jd.jumlah END 
    ELSE 0 END) as beban,
    
    SUM(CASE WHEN a.tipe_akun = 'pendapatan' THEN 
        CASE WHEN jd.posisi = 'kredit' THEN jd.jumlah ELSE -jd.jumlah END 
    ELSE 0 END) - 
    SUM(CASE WHEN a.tipe_akun = 'beban' THEN 
        CASE WHEN jd.posisi = 'debit' THEN jd.jumlah ELSE -jd.jumlah END 
    ELSE 0 END) as laba_rugi
FROM jurnal_detail jd
JOIN jurnal j ON jd.jurnal_id = j.id
JOIN akun a ON jd.akun_id = a.id
WHERE j.desa_id = 1
  AND j.status = 'posted'
  AND YEAR(j.tanggal_transaksi) = 2025
GROUP BY MONTH(j.tanggal_transaksi)
ORDER BY bulan;

-- =====================================================
-- 12. TOP 5 BEBAN TERBESAR
-- =====================================================
SELECT 
    a.kode_akun,
    a.nama_akun,
    SUM(CASE WHEN jd.posisi = 'debit' THEN jd.jumlah ELSE -jd.jumlah END) as total_beban
FROM akun a
JOIN jurnal_detail jd ON a.id = jd.akun_id
JOIN jurnal j ON jd.jurnal_id = j.id
WHERE j.desa_id = 1
  AND j.status = 'posted'
  AND a.tipe_akun = 'beban'
  AND YEAR(j.tanggal_transaksi) = 2025
  AND MONTH(j.tanggal_transaksi) = 1
GROUP BY a.id, a.kode_akun, a.nama_akun
ORDER BY total_beban DESC
LIMIT 5;

-- =====================================================
-- 13. JURNAL YANG BELUM DI-POST (DRAFT)
-- =====================================================
SELECT 
    j.id,
    j.nomor_jurnal,
    j.tanggal_transaksi,
    j.jenis_jurnal,
    j.keterangan,
    j.total_debit,
    j.total_kredit,
    j.created_at,
    u.nama as dibuat_oleh
FROM jurnal j
LEFT JOIN users u ON j.created_by = u.id
WHERE j.desa_id = 1
  AND j.status = 'draft'
ORDER BY j.created_at DESC;

-- =====================================================
-- 14. AUDIT TRAIL - PERUBAHAN JURNAL
-- =====================================================
SELECT 
    j.nomor_jurnal,
    j.tanggal_transaksi,
    j.keterangan,
    j.created_at as dibuat_pada,
    uc.nama as dibuat_oleh,
    j.updated_at as diupdate_pada,
    uu.nama as diupdate_oleh,
    j.status
FROM jurnal j
LEFT JOIN users uc ON j.created_by = uc.id
LEFT JOIN users uu ON j.updated_by = uu.id
WHERE j.desa_id = 1
  AND j.updated_at IS NOT NULL
  AND j.updated_at > j.created_at
ORDER BY j.updated_at DESC;

-- =====================================================
-- 15. REKAP TRANSAKSI PER UNIT USAHA
-- =====================================================
SELECT 
    COALESCE(u.nama_unit, 'Tidak ada unit') as unit_usaha,
    COUNT(j.id) as jumlah_jurnal,
    SUM(j.total_debit) as total_transaksi,
    MIN(j.tanggal_transaksi) as transaksi_pertama,
    MAX(j.tanggal_transaksi) as transaksi_terakhir
FROM jurnal j
LEFT JOIN unit_usaha u ON j.unit_usaha_id = u.id
WHERE j.desa_id = 1
  AND j.status = 'posted'
  AND YEAR(j.tanggal_transaksi) = 2025
GROUP BY u.id, u.nama_unit
ORDER BY total_transaksi DESC;

-- =====================================================
-- CATATAN PENGGUNAAN:
-- 1. Ganti 'desa_id = 1' dengan ID desa yang sesuai
-- 2. Ganti tahun dan bulan sesuai periode yang diinginkan
-- 3. Untuk performa optimal, pastikan index sudah dibuat
-- 4. Query ini bisa digunakan untuk debugging atau analisis
-- =====================================================
