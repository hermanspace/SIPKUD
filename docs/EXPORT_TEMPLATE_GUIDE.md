# Template Guide - Menambahkan Export ke Laporan Baru

Panduan ini menjelaskan cara menambahkan fitur export Excel dan PDF ke laporan baru dengan mengikuti pattern yang sudah ada.

## 📋 Checklist

- [ ] Buat Export Class untuk Excel
- [ ] Buat PDF Template View
- [ ] Update Livewire Component
- [ ] Update Blade View
- [ ] Test Export

## 🔧 Step-by-Step Guide

### Step 1: Buat Export Class untuk Excel

**Lokasi:** `app/Exports/NamaLaporanExport.php`

```php
<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\Color;

class NamaLaporanExport
{
    protected Collection $data;
    protected ?int $bulan;
    protected ?int $tahun;
    protected ?string $kecamatanNama;
    protected ?string $desaNama;

    public function __construct(
        Collection $data,
        ?int $bulan = null,
        ?int $tahun = null,
        ?string $kecamatanNama = null,
        ?string $desaNama = null
    ) {
        $this->data = $data;
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->kecamatanNama = $kecamatanNama;
        $this->desaNama = $desaNama;
    }

    public function export(string $filePath): void
    {
        $writer = new Writer();
        $writer->openToFile($filePath);

        // Header style
        $headerStyle = new Style();
        $headerStyle->setFontBold();
        $headerStyle->setFontSize(12);
        $headerStyle->setBackgroundColor(Color::rgb(79, 129, 189));
        $headerStyle->setFontColor(Color::WHITE);

        // Title
        $titleStyle = new Style();
        $titleStyle->setFontBold();
        $titleStyle->setFontSize(14);

        $writer->addRow(Row::fromValues(['JUDUL LAPORAN'], $titleStyle));
        
        // Periode
        if ($this->bulan && $this->tahun) {
            $periode = \Carbon\Carbon::create($this->tahun, $this->bulan, 1)
                ->translatedFormat('F Y');
            $writer->addRow(Row::fromValues(["Periode: {$periode}"]));
        }
        
        if ($this->kecamatanNama) {
            $writer->addRow(Row::fromValues(["Kecamatan: {$this->kecamatanNama}"]));
        }
        
        if ($this->desaNama) {
            $writer->addRow(Row::fromValues(["Desa: {$this->desaNama}"]));
        }
        
        $writer->addRow(Row::fromValues([''])); // Empty row

        // Table headers
        $headers = ['No', 'Kolom 1', 'Kolom 2', 'Kolom 3'];
        $writer->addRow(Row::fromValues($headers, $headerStyle));

        // Data rows
        $no = 1;
        foreach ($this->data as $item) {
            $writer->addRow(Row::fromValues([
                $no++,
                $item['field1'],
                $item['field2'],
                number_format($item['field3'], 0, ',', '.')
            ]));
        }

        $writer->close();
    }
}
```

### Step 2: Buat PDF Template View

**Lokasi:** `resources/views/pdf/nama-laporan.blade.php`

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nama Laporan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            padding: 15px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .header .info {
            font-size: 11px;
            margin-bottom: 3px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        table th {
            background-color: #4f81bd;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #2d5a8b;
        }
        
        table td {
            padding: 6px 5px;
            border: 1px solid #ddd;
        }
        
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .footer {
            margin-top: 20px;
            font-size: 9px;
            color: #666;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>JUDUL LAPORAN</h1>
        @if($periode)
            <div class="info">Periode: {{ $periode }}</div>
        @endif
        @if($kecamatanNama)
            <div class="info">Kecamatan: {{ $kecamatanNama }}</div>
        @endif
        @if($desaNama)
            <div class="info">Desa: {{ $desaNama }}</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="30%">Kolom 1</th>
                <th width="30%">Kolom 2</th>
                <th width="35%" class="text-right">Kolom 3</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item['field1'] }}</td>
                    <td>{{ $item['field2'] }}</td>
                    <td class="text-right">
                        Rp {{ number_format($item['field3'], 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}
    </div>
</body>
</html>
```

### Step 3: Update Livewire Component

**Lokasi:** `app/Livewire/Laporan/NamaLaporan.php`

```php
<?php

namespace App\Livewire\Laporan;

use App\Exports\NamaLaporanExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NamaLaporan extends Component
{
    // ... existing properties ...

    public function exportExcel(): StreamedResponse
    {
        $data = $this->getReportData();
        
        $kecamatanNama = null;
        $desaNama = null;
        
        if ($this->kecamatan_id) {
            $kecamatan = Kecamatan::find($this->kecamatan_id);
            $kecamatanNama = $kecamatan?->nama_kecamatan;
        }
        
        if ($this->desa_id) {
            $desa = Desa::find($this->desa_id);
            $desaNama = $desa?->nama_desa;
        }
        
        $export = new NamaLaporanExport(
            $data,
            $this->bulan,
            $this->tahun,
            $kecamatanNama,
            $desaNama
        );
        
        $fileName = 'nama-laporan-' . now()->format('Y-m-d-His') . '.xlsx';
        $tempFile = storage_path('app/temp/' . $fileName);
        
        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        $export->export($tempFile);
        
        return response()->stream(function () use ($tempFile) {
            echo file_get_contents($tempFile);
            unlink($tempFile);
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportPdf()
    {
        $data = $this->getReportData();
        
        $kecamatanNama = null;
        $desaNama = null;
        
        if ($this->kecamatan_id) {
            $kecamatan = Kecamatan::find($this->kecamatan_id);
            $kecamatanNama = $kecamatan?->nama_kecamatan;
        }
        
        if ($this->desa_id) {
            $desa = Desa::find($this->desa_id);
            $desaNama = $desa?->nama_desa;
        }
        
        $periode = '';
        if ($this->bulan && $this->tahun) {
            $periode = \Carbon\Carbon::create($this->tahun, $this->bulan, 1)
                ->translatedFormat('F Y');
        } elseif ($this->tahun) {
            $periode = "Tahun {$this->tahun}";
        }
        
        // Choose orientation: 'portrait' or 'landscape'
        $pdf = Pdf::loadView('pdf.nama-laporan', [
            'data' => $data,
            'periode' => $periode,
            'kecamatanNama' => $kecamatanNama,
            'desaNama' => $desaNama,
        ])->setPaper('a4', 'portrait');
        
        $fileName = 'nama-laporan-' . now()->format('Y-m-d-His') . '.pdf';
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }
    
    protected function getReportData()
    {
        // Get and return report data with filters applied
        // This method should contain all query logic
        
        $user = Auth::user();
        
        $query = Model::query();
        
        // Apply filters based on role
        if ($this->desa_id) {
            $query->where('desa_id', $this->desa_id);
        } elseif ($this->kecamatan_id) {
            $query->whereHas('desa', fn($q) => $q->where('kecamatan_id', $this->kecamatan_id));
        } elseif ($user->isAdminKecamatan()) {
            $query->whereHas('desa', fn($q) => $q->where('kecamatan_id', $user->kecamatan_id));
        } elseif ($user->isAdminDesa()) {
            $query->where('desa_id', $user->desa_id);
        }
        
        // Apply date filters
        if ($this->bulan && $this->tahun) {
            $query->whereMonth('tanggal', $this->bulan)
                  ->whereYear('tanggal', $this->tahun);
        } elseif ($this->tahun) {
            $query->whereYear('tanggal', $this->tahun);
        }
        
        return $query->get();
    }

    public function render()
    {
        $data = $this->getReportData();
        
        // ... other view data ...
        
        return view('livewire.laporan.nama-laporan', [
            'data' => $data,
            // ... other variables ...
        ]);
    }
}
```

### Step 4: Update Blade View

**Lokasi:** `resources/views/livewire/laporan/nama-laporan.blade.php`

Tambahkan export buttons di header:

```blade
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold">Nama Laporan</h2>
    <div class="flex gap-2">
        <!-- Export Buttons -->
        <button wire:click="exportExcel" 
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export Excel
        </button>
        <button wire:click="exportPdf" 
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            Export PDF
        </button>
    </div>
</div>
```

### Step 5: Test Export

Checklist testing:
- [ ] Export Excel berfungsi
- [ ] Export PDF berfungsi
- [ ] Data sesuai dengan tampilan web
- [ ] Filter diterapkan dengan benar
- [ ] File dapat dibuka dengan aplikasi yang sesuai
- [ ] Format dan styling sesuai
- [ ] Test dengan berbagai role user
- [ ] Test dengan data kosong
- [ ] Test dengan data banyak

## 🎨 Customization Tips

### Excel Styling Options

```php
// Font
$style->setFontBold();
$style->setFontItalic();
$style->setFontSize(12);
$style->setFontName('Arial');

// Colors
$style->setBackgroundColor(Color::rgb(79, 129, 189));
$style->setFontColor(Color::WHITE);

// Borders
$style->setBorder(new Border(
    new BorderPart(Border::BOTTOM, Color::BLACK, Border::WIDTH_THIN)
));
```

### PDF Layout Options

```php
// Paper size
->setPaper('a4', 'portrait')  // or 'landscape'
->setPaper('letter', 'portrait')
->setPaper([0, 0, 612, 792])  // custom size in points

// Options
->setOptions([
    'dpi' => 150,
    'defaultFont' => 'sans-serif'
])
```

### PDF CSS Tips

```css
/* Use simple layouts */
table { width: 100%; border-collapse: collapse; }

/* Avoid flexbox/grid */
.row { display: block; }

/* Use inline styles for critical styling */
<td style="font-weight: bold; color: red;">

/* Page breaks */
.page-break { page-break-after: always; }

/* Print-specific */
@media print {
    .no-print { display: none; }
}
```

## 📝 Naming Conventions

| Item | Convention | Example |
|------|------------|---------|
| Export Class | PascalCase + Export | `LaporanKeuanganExport` |
| PDF Template | kebab-case | `laporan-keuangan.blade.php` |
| Method | camelCase | `exportExcel()`, `exportPdf()` |
| File Name | kebab-case-timestamp | `laporan-keuangan-2025-12-25-143022.xlsx` |

## ⚠️ Common Pitfalls

1. **Lupa clear cache** setelah perubahan
2. **Temp directory tidak writable**
3. **Output sebelum streaming** (echo, var_dump, dll)
4. **Complex CSS di PDF** yang tidak support
5. **Memory limit** untuk data besar
6. **Timezone** untuk timestamp
7. **Number formatting** untuk currency
8. **Eager loading** untuk avoid N+1 query

## ✅ Best Practices

1. ✅ Always use `getReportData()` method untuk reusability
2. ✅ Apply same filters as view
3. ✅ Use eager loading untuk relations
4. ✅ Format numbers dengan pemisah ribuan
5. ✅ Include periode dan wilayah di header
6. ✅ Add timestamp di footer
7. ✅ Clean up temp files
8. ✅ Handle empty data gracefully
9. ✅ Test dengan real data
10. ✅ Document any custom logic

## 🚀 Advanced Features

### Queue-based Export (untuk data besar)

```php
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateReportJob implements ShouldQueue
{
    use Queueable;
    
    public function handle()
    {
        // Generate export
        // Store to storage
        // Notify user
    }
}
```

### Email Export

```php
public function emailReport()
{
    $pdf = Pdf::loadView('pdf.laporan', $data);
    
    Mail::to(auth()->user()->email)
        ->send(new ReportMail($pdf->output()));
}
```

### Scheduled Export

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        // Generate monthly report
    })->monthlyOn(1, '08:00');
}
```

## 📚 References

- [OpenSpout Documentation](https://github.com/openspout/openspout)
- [Laravel DomPDF](https://github.com/barryvdh/laravel-dompdf)
- [DomPDF Documentation](https://github.com/dompdf/dompdf)
- [CSS for PDF](https://github.com/dompdf/dompdf/wiki/CSSCompatibility)

---

**Happy Coding! 🎉**

