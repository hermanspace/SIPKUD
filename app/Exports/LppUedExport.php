<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LppUedExport
{
    protected Collection $laporan;

    protected ?int $bulan;

    protected ?int $tahun;

    protected ?string $kecamatanNama;

    protected ?string $desaNama;

    public function __construct(
        Collection $laporan,
        ?int $bulan = null,
        ?int $tahun = null,
        ?string $kecamatanNama = null,
        ?string $desaNama = null
    ) {
        $this->laporan = $laporan;
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
        $sheet->setCellValue('A'.$row, 'LAPORAN LPP UED');
        $sheet->mergeCells('A'.$row.':I'.$row);
        $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        // Periode
        if ($this->bulan && $this->tahun) {
            $periode = Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y');
            $sheet->setCellValue('A'.$row, "Periode: {$periode}");
            $sheet->mergeCells('A'.$row.':I'.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

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

        $row++; // Empty row

        // Header row
        $headerRow = $row;
        $headers = ['No', 'NIK', 'Nama Anggota', 'Nomor Pinjaman', 'Jumlah Pinjaman', 'Total Angsuran Pokok', 'Total Jasa', 'Sisa Pinjaman', 'Status'];
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
        $totalAngsuranPokok = 0;
        $totalJasa = 0;
        $totalSisaPinjaman = 0;

        foreach ($this->laporan as $item) {
            $totalJumlahPinjaman += $item['jumlah_pinjaman'];
            $totalAngsuranPokok += $item['total_angsuran_pokok'];
            $totalJasa += $item['total_jasa'];
            $totalSisaPinjaman += $item['sisa_pinjaman'];

            $statusLabel = match ($item['status_pinjaman']) {
                'aktif' => 'Aktif',
                'lunas' => 'Lunas',
                'nunggak' => 'Nunggak',
                default => $item['status_pinjaman']
            };

            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValueExplicit('B'.$row, $item['nomor_anggota'], DataType::TYPE_STRING);
            $sheet->setCellValue('C'.$row, $item['nama_anggota']);
            $sheet->setCellValue('D'.$row, $item['nomor_pinjaman']);
            $sheet->setCellValue('E'.$row, $item['jumlah_pinjaman']);
            $sheet->setCellValue('F'.$row, $item['total_angsuran_pokok']);
            $sheet->setCellValue('G'.$row, $item['total_jasa']);
            $sheet->setCellValue('H'.$row, $item['sisa_pinjaman']);
            $sheet->setCellValue('I'.$row, $statusLabel);

            // Format numbers
            $sheet->getStyle('E'.$row.':H'.$row)->getNumberFormat()
                ->setFormatCode('#,##0');

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
        $totalRow = $row;
        $sheet->setCellValue('A'.$row, '');
        $sheet->setCellValue('B'.$row, '');
        $sheet->setCellValue('C'.$row, '');
        $sheet->setCellValue('D'.$row, 'TOTAL');
        $sheet->setCellValue('E'.$row, $totalJumlahPinjaman);
        $sheet->setCellValue('F'.$row, $totalAngsuranPokok);
        $sheet->setCellValue('G'.$row, $totalJasa);
        $sheet->setCellValue('H'.$row, $totalSisaPinjaman);
        $sheet->setCellValue('I'.$row, '');

        // Style total row
        $sheet->getStyle('A'.$totalRow.':I'.$totalRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F4FF'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $sheet->getStyle('E'.$totalRow.':H'.$totalRow)->getNumberFormat()
            ->setFormatCode('#,##0');

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(12);

        // Alignment
        $sheet->getStyle('A'.($headerRow + 1).':A'.$row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D'.($headerRow + 1).':D'.$row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E'.($headerRow + 1).':H'.$row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('I'.($headerRow + 1).':I'.$row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
    }
}
