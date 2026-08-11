<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BukuKasExport
{
    protected Collection $transaksi;

    protected float $saldoAwal;

    protected ?int $bulan;

    protected ?int $tahun;

    protected ?string $kecamatanNama;

    protected ?string $desaNama;

    public function __construct(
        Collection $transaksi,
        float $saldoAwal,
        ?int $bulan = null,
        ?int $tahun = null,
        ?string $kecamatanNama = null,
        ?string $desaNama = null
    ) {
        $this->transaksi = $transaksi;
        $this->saldoAwal = $saldoAwal;
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
        $sheet->setCellValue('A'.$row, 'BUKU KAS USP');
        $sheet->mergeCells('A'.$row.':F'.$row);
        $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        // Periode
        if ($this->bulan && $this->tahun) {
            $periode = Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y');
            $sheet->setCellValue('A'.$row, "Periode: {$periode}");
            $sheet->mergeCells('A'.$row.':F'.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        } elseif ($this->tahun) {
            $sheet->setCellValue('A'.$row, "Periode: Tahun {$this->tahun}");
            $sheet->mergeCells('A'.$row.':F'.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        if ($this->kecamatanNama) {
            $sheet->setCellValue('A'.$row, "Kecamatan: {$this->kecamatanNama}");
            $sheet->mergeCells('A'.$row.':F'.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        if ($this->desaNama) {
            $sheet->setCellValue('A'.$row, "Desa: {$this->desaNama}");
            $sheet->mergeCells('A'.$row.':F'.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $row++; // Empty row

        // Header row
        $headerRow = $row;
        $headers = ['No', 'Tanggal', 'Keterangan', 'Debet', 'Kredit', 'Saldo'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.$row, $header);
            $col++;
        }

        // Style header
        $sheet->getStyle('A'.$headerRow.':F'.$headerRow)->applyFromArray([
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

        // Saldo awal row
        $saldoAwalRow = $row;
        $sheet->setCellValue('A'.$row, '');
        $sheet->setCellValue('B'.$row, '');
        $sheet->setCellValue('C'.$row, 'SALDO AWAL');
        $sheet->setCellValue('D'.$row, '');
        $sheet->setCellValue('E'.$row, '');
        $sheet->setCellValue('F'.$row, $this->saldoAwal);

        $sheet->getStyle('A'.$saldoAwalRow.':F'.$saldoAwalRow)->applyFromArray([
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

        $sheet->getStyle('F'.$saldoAwalRow)->getNumberFormat()->setFormatCode('#,##0');

        $row++;

        // Data rows
        $no = 1;
        $saldoBerjalan = $this->saldoAwal;

        foreach ($this->transaksi as $item) {
            if ($item->jenis_transaksi === 'masuk') {
                $saldoBerjalan += $item->jumlah;
            } else {
                $saldoBerjalan -= $item->jumlah;
            }

            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $item->tanggal_transaksi->format('d/m/Y'));
            $sheet->setCellValue('C'.$row, $item->uraian ?? '-');
            $sheet->setCellValue('D'.$row, $item->jenis_transaksi === 'masuk' ? $item->jumlah : '');
            $sheet->setCellValue('E'.$row, $item->jenis_transaksi === 'keluar' ? $item->jumlah : '');
            $sheet->setCellValue('F'.$row, $saldoBerjalan);

            // Format numbers
            if ($item->jenis_transaksi === 'masuk') {
                $sheet->getStyle('D'.$row)->getNumberFormat()->setFormatCode('#,##0');
            }
            if ($item->jenis_transaksi === 'keluar') {
                $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode('#,##0');
            }
            $sheet->getStyle('F'.$row)->getNumberFormat()->setFormatCode('#,##0');

            // Borders
            $sheet->getStyle('A'.$row.':F'.$row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);

            $row++;
        }

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);

        // Alignment
        $sheet->getStyle('A'.($headerRow + 1).':A'.$row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B'.($headerRow + 1).':B'.$row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D'.($headerRow + 1).':F'.$row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
    }
}
