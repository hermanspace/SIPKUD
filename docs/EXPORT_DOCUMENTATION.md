# Dokumentasi Fitur Export Laporan

## Overview
Fitur export laporan memungkinkan user untuk mengunduh laporan dalam format Excel (XLSX) dan PDF untuk 3 jenis laporan:
1. **Buku Kas USP** - Laporan transaksi kas masuk dan keluar
2. **Laporan Akhir USP** - Laporan keuangan akhir periode
3. **LPP UED** - Laporan pinjaman dan angsuran

## Package yang Digunakan

### 1. OpenSpout (untuk Excel)
```bash
composer require openspout/openspout
```
- Library untuk membaca dan menulis file spreadsheet
- Mendukung format XLSX, ODS, dan CSV
- Lebih ringan dan cepat dibanding PHPExcel/PhpSpreadsheet

### 2. Laravel DomPDF (untuk PDF)
```bash
composer require barryvdh/laravel-dompdf
```
- Package Laravel untuk generate PDF menggunakan DomPDF
- Support HTML/CSS untuk styling PDF
- Mudah diintegrasikan dengan Blade templates

## Struktur File

### Export Classes
Lokasi: `app/Exports/`

1. **BukuKasExport.php** - Export Buku Kas ke Excel
2. **LaporanAkhirUspExport.php** - Export Laporan Akhir USP ke Excel
3. **LppUedExport.php** - Export LPP UED ke Excel

### PDF Templates
Lokasi: `resources/views/pdf/`

1. **buku-kas.blade.php** - Template PDF untuk Buku Kas
2. **laporan-akhir-usp.blade.php** - Template PDF untuk Laporan Akhir USP
3. **lpp-ued.blade.php** - Template PDF untuk LPP UED

### Livewire Components (Updated)
Lokasi: `app/Livewire/Laporan/`

Setiap component telah ditambahkan method:
- `exportExcel()` - Generate dan download file Excel
- `exportPdf()` - Generate dan download file PDF
- `getReportData()` - Helper method untuk mengambil data laporan

## Cara Kerja

### Export Excel

1. User klik tombol "Export Excel" di halaman laporan
2. Livewire method `exportExcel()` dipanggil
3. Data laporan diambil dengan filter yang aktif (periode, wilayah, dll)
4. Instance Export class dibuat dengan data tersebut
5. File Excel digenerate menggunakan OpenSpout
6. File disimpan sementara di `storage/app/temp/`
7. File di-stream ke browser untuk download
8. File temporary dihapus setelah download

### Export PDF

1. User klik tombol "Export PDF" di halaman laporan
2. Livewire method `exportPdf()` dipanggil
3. Data laporan diambil dengan filter yang aktif
4. Blade template PDF di-render dengan data tersebut
5. DomPDF mengkonversi HTML ke PDF
6. PDF di-stream ke browser untuk download

## Fitur Export

### 1. Buku Kas USP

**Format Excel:**
- Header dengan informasi periode dan wilayah
- Tabel dengan kolom: No, Tanggal, Keterangan, Debet, Kredit, Saldo
- Baris saldo awal
- Data transaksi dengan saldo berjalan
- Styling dengan warna header biru

**Format PDF:**
- Layout landscape (A4)
- Header dengan judul dan info periode/wilayah
- Tabel transaksi dengan warna alternatif per baris
- Baris saldo awal dengan highlight
- Footer dengan timestamp cetak

### 2. Laporan Akhir USP

**Format Excel:**
- Header dengan informasi periode dan wilayah
- Section Pendapatan (Jasa, Denda, Total)
- Section SHU dengan persentase
- Section Pinjaman (Tersalurkan, Terbayar, Aktif, Sisa)
- Format nilai dengan pemisah ribuan

**Format PDF:**
- Layout portrait (A4)
- Card-style sections dengan warna background
- Border dan styling yang clean
- Highlight untuk total values
- Footer dengan timestamp cetak

### 3. LPP UED

**Format Excel:**
- Header dengan informasi periode dan wilayah
- Tabel detail pinjaman per anggota
- Kolom: No, NIK, Nama, No. Pinjaman, Jumlah, Pokok, Jasa, Sisa, Status
- Baris total di akhir tabel
- Format angka dengan pemisah ribuan

**Format PDF:**
- Layout landscape (A4)
- Tabel dengan font size kecil untuk muat banyak kolom
- Status badge dengan warna (Aktif=hijau, Lunas=biru, Nunggak=merah)
- Baris total dengan highlight
- Footer dengan timestamp cetak

## Filter yang Diterapkan

Semua export akan mengikuti filter yang aktif di halaman:

1. **Filter Periode:**
   - Bulan (opsional)
   - Tahun (wajib untuk beberapa laporan)

2. **Filter Wilayah:**
   - Kecamatan (untuk Super Admin)
   - Desa (untuk Super Admin dan Admin Kecamatan)
   - Otomatis terbatas sesuai role user

3. **Filter Role:**
   - Super Admin: Bisa lihat semua data
   - Admin Kecamatan: Terbatas pada kecamatannya
   - Admin Desa: Terbatas pada desanya

## Nama File

Format nama file yang digenerate:
- Excel: `{jenis-laporan}-{timestamp}.xlsx`
- PDF: `{jenis-laporan}-{timestamp}.pdf`

Contoh:
- `buku-kas-2025-12-25-143022.xlsx`
- `laporan-akhir-usp-2025-12-25-143022.pdf`
- `lpp-ued-2025-12-25-143022.xlsx`

## Keamanan

1. **Authorization:** Semua export method menggunakan data yang sudah difilter sesuai role user
2. **Temporary Files:** File Excel temporary dihapus setelah download
3. **Stream Response:** File tidak disimpan permanent di server
4. **Permission Check:** Menggunakan Gate policy yang sama dengan view laporan

## Troubleshooting

### Error: "Class 'Barryvdh\DomPDF\Facade\Pdf' not found"
**Solusi:** Jalankan `php artisan config:clear` dan `composer dump-autoload`

### Error: "Failed to create temp directory"
**Solusi:** Pastikan folder `storage/app/temp` ada dan writable
```bash
mkdir -p storage/app/temp
chmod 755 storage/app/temp
```

### PDF tidak menampilkan karakter Indonesia dengan benar
**Solusi:** Sudah menggunakan font Arial yang support karakter Indonesia

### Excel file corrupt atau tidak bisa dibuka
**Solusi:** Pastikan tidak ada output lain sebelum streaming file (cek error log)

## Testing

Untuk test fitur export:

1. Login sebagai user dengan role yang berbeda (Super Admin, Admin Kecamatan, Admin Desa)
2. Buka setiap halaman laporan
3. Set berbagai kombinasi filter (periode, wilayah)
4. Klik tombol "Export Excel" dan "Export PDF"
5. Verifikasi:
   - File ter-download dengan benar
   - Data sesuai dengan yang ditampilkan di halaman
   - Format dan styling sesuai
   - Filter diterapkan dengan benar

## Future Improvements

1. **Caching:** Cache data laporan untuk export yang sama dalam periode tertentu
2. **Queue:** Untuk laporan besar, gunakan queue untuk generate file di background
3. **Email:** Opsi untuk mengirim laporan via email
4. **Schedule:** Schedule otomatis untuk generate laporan periodik
5. **Template Customization:** Allow user customize template PDF/Excel
6. **Compression:** Compress file untuk laporan besar
7. **Multiple Format:** Support format lain seperti CSV, ODS

## Maintenance

### Update Package
```bash
composer update openspout/openspout barryvdh/laravel-dompdf
```

### Clear Cache
```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

### Check Logs
Jika ada error saat export, check:
- `storage/logs/laravel.log`
- Browser console untuk error Livewire
- Network tab untuk response error

## Support

Untuk pertanyaan atau issue terkait fitur export:
1. Check dokumentasi package: [OpenSpout](https://github.com/openspout/openspout) dan [Laravel DomPDF](https://github.com/barryvdh/laravel-dompdf)
2. Check Laravel log untuk error detail
3. Pastikan semua dependency terinstall dengan benar

