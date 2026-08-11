# Quick Reference - Export Laporan

## 🚀 Quick Start

### Install (Sudah Selesai)
```bash
composer require openspout/openspout barryvdh/laravel-dompdf
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
php artisan config:clear
```

### Setup Temp Directory
```bash
mkdir -p storage/app/temp
chmod 755 storage/app/temp
```

## 📁 File Structure

```
app/
├── Exports/
│   ├── BukuKasExport.php
│   ├── LaporanAkhirUspExport.php
│   └── LppUedExport.php
└── Livewire/Laporan/
    ├── BukuKas.php (updated)
    ├── LaporanAkhirUsp.php (updated)
    └── LppUed.php (updated)

resources/views/
├── pdf/
│   ├── buku-kas.blade.php
│   ├── laporan-akhir-usp.blade.php
│   └── lpp-ued.blade.php
└── livewire/laporan/
    ├── buku-kas.blade.php (updated)
    ├── laporan-akhir-usp.blade.php (updated)
    └── lpp-ued.blade.php (updated)
```

## 🎯 Laporan yang Tersedia

| Laporan | Excel | PDF | Layout |
|---------|-------|-----|--------|
| Buku Kas USP | ✅ | ✅ | Landscape |
| Laporan Akhir USP | ✅ | ✅ | Portrait |
| LPP UED | ✅ | ✅ | Landscape |

## 💻 Usage

### Di Livewire Component

```php
// Export Excel
public function exportExcel(): StreamedResponse
{
    $data = $this->getReportData();
    $export = new BukuKasExport($data, ...);
    // ... generate and stream
}

// Export PDF
public function exportPdf()
{
    $data = $this->getReportData();
    $pdf = Pdf::loadView('pdf.buku-kas', $data);
    // ... generate and stream
}
```

### Di Blade View

```blade
<!-- Export Buttons -->
<button wire:click="exportExcel">Export Excel</button>
<button wire:click="exportPdf">Export PDF</button>
```

## 🔧 Common Tasks

### Clear Cache
```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

### Check Logs
```bash
tail -f storage/logs/laravel.log
```

### Update Packages
```bash
composer update openspout/openspout barryvdh/laravel-dompdf
```

## 🎨 Customization

### Excel Styling
Edit file di `app/Exports/{Report}Export.php`:
```php
$headerStyle = new Style();
$headerStyle->setFontBold();
$headerStyle->setFontSize(12);
$headerStyle->setBackgroundColor(Color::rgb(79, 129, 189));
```

### PDF Styling
Edit file di `resources/views/pdf/{report}.blade.php`:
```html
<style>
    body { font-family: 'Arial'; font-size: 11px; }
    .header { background-color: #4f81bd; }
</style>
```

## 🐛 Troubleshooting

### Error: Class not found
```bash
composer dump-autoload
php artisan config:clear
```

### Error: Permission denied
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### PDF blank atau error
- Check `storage/logs/laravel.log`
- Simplify HTML/CSS
- Test dengan data minimal

### Excel corrupt
- Check tidak ada output sebelum streaming
- Verify temp directory writable
- Check error log

## 📊 Data Flow

```
User Click Export
    ↓
Livewire Method (exportExcel/exportPdf)
    ↓
Get Report Data (with filters)
    ↓
Create Export Instance / Load PDF View
    ↓
Generate File (Excel/PDF)
    ↓
Stream to Browser
    ↓
Download File
    ↓
Clean up (delete temp file for Excel)
```

## 🔐 Security

- ✅ Role-based filtering otomatis
- ✅ No permanent file storage
- ✅ Temp files cleaned after download
- ✅ Same permissions as view

## 📝 File Naming

Format: `{report-name}-{timestamp}.{ext}`

Examples:
- `buku-kas-2025-12-25-143022.xlsx`
- `laporan-akhir-usp-2025-12-25-143022.pdf`
- `lpp-ued-2025-12-25-143022.xlsx`

## 🎯 Testing URLs

```
/laporan/buku-kas
/laporan/laporan-akhir-usp
/laporan/lpp-ued
```

## 📞 Quick Help

### Package Docs:
- OpenSpout: https://github.com/openspout/openspout
- DomPDF: https://github.com/barryvdh/laravel-dompdf

### Common Issues:
1. **Export tidak jalan**: Clear cache, check logs
2. **File corrupt**: Check output sebelum streaming
3. **Slow export**: Consider queue untuk data besar
4. **Memory error**: Increase PHP memory_limit

### Performance Tips:
- Limit data dengan pagination
- Use eager loading untuk relations
- Cache data jika memungkinkan
- Consider queue untuk laporan besar

## ✅ Checklist Deploy

- [ ] Install packages
- [ ] Publish configs
- [ ] Create temp directory
- [ ] Set permissions
- [ ] Clear cache
- [ ] Test each export
- [ ] Monitor logs
- [ ] Check disk space

## 🚨 Emergency Commands

```bash
# Reset everything
php artisan optimize:clear
composer dump-autoload

# Fix permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

# Check disk space
df -h

# Clear temp files manually
rm -rf storage/app/temp/*

# Restart services (if needed)
php artisan queue:restart
```

---

**Last Updated:** December 25, 2025
**Version:** 1.0.0
**Status:** ✅ Production Ready

