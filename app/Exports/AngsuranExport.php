<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AngsuranExport
{
    protected Collection $angsuran;

    protected ?string $kecamatanNama;

    protected ?string $desaNama;

    protected ?string $pinjamanNomor;

    public function __construct(
        Collection $angsuran,
        ?string $kecamatanNama = null,
        ?string $desaNama = null,
        ?string $pinjamanNomor = null
    ) {
        $this->angsuran = $angsuran;
        $this->kecamatanNama = $kecamatanNama;
        $this->desaNama = $desaNama;
        $this->pinjamanNomor = $pinjamanNomor;
    }

    public function export(string $filePath): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $row = 1;

        // Title
        $sheet->setCellValue('A'.$row, 'DATA MASTER ANGSURAN PINJAMAN USP/UED-SP');
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

        if ($this->pinjamanNomor) {
            $sheet->setCellValue('A'.$row, "No. Pinjaman: {$this->pinjamanNomor}");
            $sheet->mergeCells('A'.$row.':I'.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $row++; // Empty row

        // Header row
        $headerRow = $row;
        $headers = ['No', 'Angsuran Ke', 'No. Pinjaman', 'Nama Anggota', 'Tanggal Bayar', 'Pokok', 'Jasa', 'Total Bayar', 'Denda'];
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
        $totalPokok = 0;
        $totalJasa = 0;
        $totalBayar = 0;
        $totalDenda = 0;

        foreach ($this->angsuran as $item) {
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $item->angsuran_ke);
            $sheet->setCellValue('C'.$row, $item->pinjaman->nomor_pinjaman ?? '-');
            $sheet->setCellValue('D'.$row, $item->pinjaman->anggota->nama ?? '-');
            $sheet->setCellValue('E'.$row, $item->tanggal_bayar ? $item->tanggal_bayar->format('d/m/Y') : '-');
            $sheet->setCellValue('F'.$row, $item->pokok_dibayar);
            $sheet->setCellValue('G'.$row, $item->jasa_dibayar);
            $sheet->setCellValue('H'.$row, $item->total_dibayar);
            $sheet->setCellValue('I'.$row, $item->denda_dibayar ?? 0);

            // Format numbers
            $sheet->getStyle('F'.$row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('G'.$row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('H'.$row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('I'.$row)->getNumberFormat()->setFormatCode('#,##0');

            $totalPokok += $item->pokok_dibayar;
            $totalJasa += $item->jasa_dibayar;
            $totalBayar += $item->total_dibayar;
            $totalDenda += $item->denda_dibayar ?? 0;

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
        if ($this->angsuran->count() > 0) {
            $sheet->setCellValue('A'.$row, '');
            $sheet->setCellValue('B'.$row, '');
            $sheet->setCellValue('C'.$row, '');
            $sheet->setCellValue('D'.$row, '');
            $sheet->setCellValue('E'.$row, 'TOTAL:');
            $sheet->setCellValue('F'.$row, $totalPokok);
            $sheet->setCellValue('G'.$row, $totalJasa);
            $sheet->setCellValue('H'.$row, $totalBayar);
            $sheet->setCellValue('I'.$row, $totalDenda);

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
            $sheet->getStyle('G'.$row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('H'.$row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('I'.$row)->getNumberFormat()->setFormatCode('#,##0');
        }

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(15);

        // Alignment
        $sheet->getStyle('A'.($headerRow + 1).':A'.($row - 1))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B'.($headerRow + 1).':B'.($row - 1))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E'.($headerRow + 1).':E'.($row - 1))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F'.($headerRow + 1).':I'.($row - 1))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
    }
}
