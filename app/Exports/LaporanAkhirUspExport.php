<?php

namespace App\Exports;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanAkhirUspExport
{
    protected array $data;

    protected ?int $bulan;

    protected ?int $tahun;

    protected ?string $kecamatanNama;

    protected ?string $desaNama;

    public function __construct(
        array $data,
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
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $row = 1;

        // Title
        $sheet->setCellValue('A'.$row, 'LAPORAN AKHIR USP');
        $sheet->mergeCells('A'.$row.':B'.$row);
        $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        // Periode
        if ($this->bulan && $this->tahun) {
            $periode = Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y');
            $sheet->setCellValue('A'.$row, "Periode: {$periode}");
            $sheet->mergeCells('A'.$row.':B'.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        } elseif ($this->tahun) {
            $sheet->setCellValue('A'.$row, "Periode: Tahun {$this->tahun}");
            $sheet->mergeCells('A'.$row.':B'.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        if ($this->kecamatanNama) {
            $sheet->setCellValue('A'.$row, "Kecamatan: {$this->kecamatanNama}");
            $sheet->mergeCells('A'.$row.':B'.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        if ($this->desaNama) {
            $sheet->setCellValue('A'.$row, "Desa: {$this->desaNama}");
            $sheet->mergeCells('A'.$row.':B'.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $row++; // Empty row

        // PENDAPATAN Section
        $sheet->setCellValue('A'.$row, 'PENDAPATAN');
        $sheet->mergeCells('A'.$row.':B'.$row);
        $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A'.$row)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD'],
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
        ]);
        $row++;

        $sheet->setCellValue('A'.$row, 'Pendapatan Jasa');
        $sheet->setCellValue('B'.$row, $this->data['totalPendapatanJasa']);
        $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode('#,##0');
        $row++;

        $sheet->setCellValue('A'.$row, 'Pendapatan Denda');
        $sheet->setCellValue('B'.$row, $this->data['totalPendapatanDenda']);
        $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode('#,##0');
        $row++;

        $sheet->setCellValue('A'.$row, 'Total Pendapatan');
        $sheet->setCellValue('B'.$row, $this->data['totalPendapatan']);
        $sheet->getStyle('A'.$row.':B'.$row)->getFont()->setBold(true);
        $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A'.$row.':B'.$row)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F4FF'],
            ],
        ]);
        $row++;

        $row++; // Empty row

        // SHU Section
        $sheet->setCellValue('A'.$row, 'SISA HASIL USAHA (SHU)');
        $sheet->mergeCells('A'.$row.':B'.$row);
        $sheet->getStyle('A'.$row)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD'],
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
        ]);
        $row++;

        $sheet->setCellValue('A'.$row, "SHU ({$this->data['persentaseShu']}% dari Total Pendapatan)");
        $sheet->setCellValue('B'.$row, $this->data['totalShu']);
        $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode('#,##0');
        $row++;

        $row++; // Empty row

        // PINJAMAN Section
        $sheet->setCellValue('A'.$row, 'PINJAMAN');
        $sheet->mergeCells('A'.$row.':B'.$row);
        $sheet->getStyle('A'.$row)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD'],
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
        ]);
        $row++;

        $sheet->setCellValue('A'.$row, 'Total Pinjaman Tersalurkan');
        $sheet->setCellValue('B'.$row, $this->data['totalPinjamanTersalurkan']);
        $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode('#,##0');
        $row++;

        $sheet->setCellValue('A'.$row, 'Total Pokok Terbayar');
        $sheet->setCellValue('B'.$row, $this->data['totalPokokTerbayar']);
        $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode('#,##0');
        $row++;

        $sheet->setCellValue('A'.$row, 'Jumlah Pinjaman Aktif');
        $sheet->setCellValue('B'.$row, $this->data['jumlahPinjamanAktif'].' pinjaman');
        $row++;

        $sheet->setCellValue('A'.$row, 'Total Sisa Pinjaman');
        $sheet->setCellValue('B'.$row, $this->data['totalSisaPinjaman']);
        $sheet->getStyle('A'.$row.':B'.$row)->getFont()->setBold(true);
        $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A'.$row.':B'.$row)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F4FF'],
            ],
        ]);

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(25);

        // Alignment
        $sheet->getStyle('B:B')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
    }
}
