<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AnggotaExport
{
    protected Collection $anggota;

    protected ?string $kecamatanNama;

    protected ?string $desaNama;

    protected ?string $kelompokNama;

    protected ?string $statusFilter;

    public function __construct(
        Collection $anggota,
        ?string $kecamatanNama = null,
        ?string $desaNama = null,
        ?string $kelompokNama = null,
        ?string $statusFilter = null
    ) {
        $this->anggota = $anggota;
        $this->kecamatanNama = $kecamatanNama;
        $this->desaNama = $desaNama;
        $this->kelompokNama = $kelompokNama;
        $this->statusFilter = $statusFilter;
    }

    public function export(string $filePath): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $row = 1;

        // Title
        $sheet->setCellValue('A'.$row, 'DATA MASTER ANGGOTA USP/UED-SP');
        $sheet->mergeCells('A'.$row.':J'.$row);
        $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        // Filter info
        if ($this->kecamatanNama) {
            $sheet->setCellValue('A'.$row, "Kecamatan: {$this->kecamatanNama}");
            $sheet->mergeCells('A'.$row.':J'.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        if ($this->desaNama) {
            $sheet->setCellValue('A'.$row, "Desa: {$this->desaNama}");
            $sheet->mergeCells('A'.$row.':J'.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        if ($this->kelompokNama) {
            $sheet->setCellValue('A'.$row, "Kelompok: {$this->kelompokNama}");
            $sheet->mergeCells('A'.$row.':J'.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        if ($this->statusFilter) {
            $sheet->setCellValue('A'.$row, 'Status: '.ucfirst($this->statusFilter));
            $sheet->mergeCells('A'.$row.':J'.$row);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $row++; // Empty row

        // Header row
        $headerRow = $row;
        $headers = ['No', 'NIK', 'Nama', 'Kelompok', 'Kecamatan', 'Desa', 'Alamat', 'No HP', 'JK', 'Tgl Gabung', 'Status'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.$row, $header);
            $col++;
        }

        // Style header
        $sheet->getStyle('A'.$headerRow.':K'.$headerRow)->applyFromArray([
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

        foreach ($this->anggota as $item) {
            $sheet->setCellValue('A'.$row, $no++);

            // NIK sebagai text
            $sheet->setCellValueExplicit('B'.$row, $item->nik ?? '-', DataType::TYPE_STRING);

            $sheet->setCellValue('C'.$row, $item->nama);
            $sheet->setCellValue('D'.$row, $item->kelompok->nama_kelompok ?? '-');
            $sheet->setCellValue('E'.$row, $item->desa->kecamatan->nama_kecamatan ?? '-');
            $sheet->setCellValue('F'.$row, $item->desa->nama_desa ?? '-');
            $sheet->setCellValue('G'.$row, $item->alamat ?? '-');
            $sheet->setCellValue('H'.$row, $item->nomor_hp ?? '-');
            $sheet->setCellValue('I'.$row, $item->jenis_kelamin === 'L' ? 'Laki-laki' : ($item->jenis_kelamin === 'P' ? 'Perempuan' : '-'));
            $sheet->setCellValue('J'.$row, $item->tanggal_gabung ? $item->tanggal_gabung->format('d/m/Y') : '-');
            $sheet->setCellValue('K'.$row, ucfirst($item->status));

            // Borders
            $sheet->getStyle('A'.$row.':K'.$row)->applyFromArray([
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
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(35);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(12);
        $sheet->getColumnDimension('J')->setWidth(12);
        $sheet->getColumnDimension('K')->setWidth(10);

        // Alignment
        $sheet->getStyle('A'.($headerRow + 1).':A'.($row - 1))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I'.($headerRow + 1).':I'.($row - 1))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J'.($headerRow + 1).':J'.($row - 1))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('K'.($headerRow + 1).':K'.($row - 1))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
    }
}
