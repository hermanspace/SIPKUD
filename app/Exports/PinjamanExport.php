<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PinjamanExport
{
    protected Collection $pinjaman;

    protected ?string $kecamatanNama;

    protected ?string $desaNama;

    protected ?string $statusFilter;

    public function __construct(
        Collection $pinjaman,
        ?string $kecamatanNama = null,
        ?string $desaNama = null,
        ?string $statusFilter = null
    ) {
        $this->pinjaman = $pinjaman;
        $this->kecamatanNama = $kecamatanNama;
        $this->desaNama = $desaNama;
        $this->statusFilter = $statusFilter;
    }

    public function export(string $filePath): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $row = 1;

        // Title
        $sheet->setCellValue('A'.$row, 'DATA MASTER PINJAMAN USP/UED-SP');
        $sheet->mergeCells('A'.$row.':I'.$row);
        $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        // Filter info
        if ($this->kecamatanNama) {
            $sheet->setCellValue('A'.$row, "Kecamatan: {$this->kecamatanNama}");
            $sheet->mergeCells('A'.$row.':I'.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        if ($this->desaNama) {
            $sheet->setCellValue('A'.$row, "Desa: {$this->desaNama}");
            $sheet->mergeCells('A'.$row.':I'.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        if ($this->statusFilter) {
            $sheet->setCellValue('A'.$row, 'Status: '.ucfirst($this->statusFilter));
            $sheet->mergeCells('A'.$row.':I'.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $row++; // Empty row

        // Header row
        $headerRow = $row;
        $headers = ['No', 'No. Pinjaman', 'Tanggal', 'Nama Anggota', 'Desa', 'Jumlah Pinjaman', 'Jangka Waktu', 'Jasa (%)', 'Status'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.$row, $header);
            $col++;
        }

        // Style header
        $sheet->getStyle('A'.$headerRow.':I'.$headerRow)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $row++;

        // Data rows
        $no = 1;
        $totalJumlahPinjaman = 0;

        foreach ($this->pinjaman as $item) {
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $item->nomor_pinjaman);
            $sheet->setCellValue('C'.$row, $item->tanggal_pinjaman->format('d/m/Y'));
            $sheet->setCellValue('D'.$row, $item->anggota->nama ?? '-');
            $sheet->setCellValue('E'.$row, $item->desa->nama_desa ?? '-');
            $sheet->setCellValue('F'.$row, $item->jumlah_pinjaman);
            $sheet->setCellValue('G'.$row, $item->jangka_waktu_bulan.' bulan');
            $sheet->setCellValue('H'.$row, $item->jasa_persen);
            $sheet->setCellValue('I'.$row, ucfirst($item->status_pinjaman));

            // Format numbers
            $sheet->getStyle('F'.$row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('H'.$row)->getNumberFormat()->setFormatCode('0.00');

            $totalJumlahPinjaman += $item->jumlah_pinjaman;

            // Borders
            $sheet->getStyle('A'.$row.':I'.$row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);

            $row++;
        }

        // Total row
        if ($this->pinjaman->count() > 0) {
            $sheet->setCellValue('A'.$row, '');
            $sheet->setCellValue('B'.$row, '');
            $sheet->setCellValue('C'.$row, '');
            $sheet->setCellValue('D'.$row, '');
            $sheet->setCellValue('E'.$row, 'TOTAL:');
            $sheet->setCellValue('F'.$row, $totalJumlahPinjaman);
            $sheet->setCellValue('G'.$row, '');
            $sheet->setCellValue('H'.$row, '');
            $sheet->setCellValue('I'.$row, '');

            $sheet->getStyle('A'.$row.':I'.$row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8F4FF'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);

            $sheet->getStyle('E'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('F'.$row)->getNumberFormat()->setFormatCode('#,##0');
        }

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(10);
        $sheet->getColumnDimension('I')->setWidth(12);

        // Alignment
        $sheet->getStyle('A'.($headerRow + 1).':A'.($row - 1))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C'.($headerRow + 1).':C'.($row - 1))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F'.($headerRow + 1).':F'.($row - 1))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G'.($headerRow + 1).':G'.($row - 1))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H'.($headerRow + 1).':H'.($row - 1))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I'.($headerRow + 1).':I'.($row - 1))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
    }
}
