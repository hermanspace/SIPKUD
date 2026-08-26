<?php

namespace App\Services;

use App\Models\Anggota;
use App\Models\AngsuranPinjaman;
use App\Models\Pinjaman;
use App\Models\SektorUsaha;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Impor data historis pemanfaat dan pinjaman dari file Excel UEK-SP warisan
 * (template kabupaten 2005, sheet "LPP-UEK"). Satu file bulanan terakhir per
 * desa sudah memuat seluruh posisi kumulatif.
 *
 * Prinsip:
 * - Anggota dibuat dengan NIK placeholder (nik_sementara) bila belum ada.
 * - Pinjaman diberi kunci no_sppk unik per desa (unggah ulang tidak duplikat).
 * - Riwayat angsuran dipecah sintetis per bulan dari kumulatif terbayar agar
 *   perhitungan kolektibilitas/NPL langsung akurat - TANPA jurnal per baris.
 * - Satu jurnal memorial saldo awal (Debit Piutang, Kredit Modal) senilai
 *   total sisa pokok pinjaman yang baru diimpor. Kas tidak tersentuh.
 */
class ImportHistorisService
{
    /** Kolom (1-based) pada sheet LPP-UEK - template seragam kabupaten. */
    private const KOL_NO = 1;

    private const KOL_SPPK = 2;

    private const KOL_NAMA = 3;

    private const KOL_JK = 4;

    private const KOL_USAHA = 5;

    private const KOL_TANGGAL = 7;

    private const KOL_TENOR = 8;

    private const KOL_POKOK = 10;

    private const KOL_JASA = 11;

    private const KOL_BAYAR_POKOK = 19;

    private const KOL_BAYAR_JASA = 20;

    private const KOL_SISA_POKOK = 22;

    private const KOL_SISA_JASA = 23;

    private const BARIS_HEADER = 5;

    private const BARIS_DATA_MULAI = 9;

    private const SEKTOR_MAP = [
        'D' => 'Perdagangan',
        'T' => 'Pertanian',
        'K' => 'Perkebunan',
        'I' => 'Perikanan',
        'TR' => 'Peternakan',
        'IK' => 'Industri Kecil',
        'J' => 'Jasa',
    ];

    public function __construct(private AccountingService $accountingService) {}

    /**
     * Baca dan validasi file. Mengembalikan ringkasan + baris siap impor.
     *
     * @return array{periode: ?string, rows: array, errors: array, ringkasan: array, kontrol: array}
     */
    public function parse(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(false);

        // Muat hanya sheet yang dibutuhkan - file asli berisi belasan sheet
        // besar (Monitoring 1200+ baris x 59 kolom) yang tidak relevan.
        $dibutuhkan = [];
        foreach ($reader->listWorksheetNames($path) as $sheetName) {
            if (strcasecmp(trim($sheetName), 'LPP-UEK') === 0
                || strcasecmp(trim($sheetName), 'LKN I') === 0) {
                $dibutuhkan[] = $sheetName;
            }
        }
        if ($dibutuhkan !== []) {
            $reader->setLoadSheetsOnly($dibutuhkan);
        }

        $spreadsheet = $reader->load($path);

        $lpp = $this->cariSheet($spreadsheet, 'LPP-UEK');
        if (! $lpp) {
            throw new \RuntimeException('Sheet "LPP-UEK" tidak ditemukan di file. Pastikan file adalah laporan bulanan UEK-SP template kabupaten.');
        }

        $this->verifikasiTemplate($lpp);

        $rows = [];
        $errors = [];
        $maxRow = $lpp->getHighestRow();

        for ($r = self::BARIS_DATA_MULAI; $r <= $maxRow; $r++) {
            $no = $this->angka($lpp, $r, self::KOL_NO);
            $nama = trim((string) $this->nilai($lpp, $r, self::KOL_NAMA));

            // Data berhenti saat nomor urut tidak lagi numerik (masuk blok TOTAL/statistik)
            if ($no === null) {
                if (strtoupper($nama) === '' && strtoupper((string) $this->nilai($lpp, $r, self::KOL_SPPK)) === 'TOTAL') {
                    break;
                }
                if (str_contains(strtoupper((string) $this->nilai($lpp, $r, 1)), 'TOTAL')) {
                    break;
                }

                continue;
            }

            if ($nama === '') {
                $errors[] = "Baris {$r}: nama pemanfaat kosong - dilewati.";

                continue;
            }

            $sppk = $this->angka($lpp, $r, self::KOL_SPPK);
            $pokok = $this->angka($lpp, $r, self::KOL_POKOK) ?? 0.0;
            $tenor = (int) ($this->angka($lpp, $r, self::KOL_TENOR) ?? 0);
            $tanggal = $this->tanggal($lpp, $r, self::KOL_TANGGAL);

            if ($sppk === null || $sppk <= 0) {
                $errors[] = "Baris {$r} ({$nama}): No SPPK kosong/tidak valid - dilewati.";

                continue;
            }
            if ($tanggal === null) {
                $errors[] = "Baris {$r} ({$nama}): tanggal pinjaman tidak terbaca - dilewati.";

                continue;
            }
            if ($pokok <= 0 || $tenor <= 0) {
                $errors[] = "Baris {$r} ({$nama}): pokok/tenor tidak valid - dilewati.";

                continue;
            }

            $jasa = $this->angka($lpp, $r, self::KOL_JASA) ?? 0.0;
            $bayarPokok = $this->angka($lpp, $r, self::KOL_BAYAR_POKOK) ?? 0.0;
            $bayarJasa = $this->angka($lpp, $r, self::KOL_BAYAR_JASA) ?? 0.0;
            $sisaPokok = $this->angka($lpp, $r, self::KOL_SISA_POKOK);
            $sisaPokok = $sisaPokok !== null ? $sisaPokok : max(0, $pokok - $bayarPokok);

            $jk = strtoupper(trim((string) $this->nilai($lpp, $r, self::KOL_JK)));
            $kodeUsaha = strtoupper(trim((string) $this->nilai($lpp, $r, self::KOL_USAHA)));

            $rows[] = [
                'baris' => $r,
                'no_sppk' => (int) $sppk,
                'nama' => $nama,
                'jenis_kelamin' => in_array($jk, ['L', 'P']) ? $jk : null,
                'sektor' => self::SEKTOR_MAP[$kodeUsaha] ?? null,
                'tanggal_pinjaman' => $tanggal->toDateString(),
                'tenor' => $tenor,
                'pokok' => round($pokok, 2),
                'jasa' => round($jasa, 2),
                'jasa_persen' => round($jasa / $pokok / $tenor * 100, 2),
                'bayar_pokok' => round($bayarPokok, 2),
                'bayar_jasa' => round($bayarJasa, 2),
                'sisa_pokok' => round($sisaPokok, 2),
                'lunas' => $sisaPokok <= 0.01,
            ];
        }

        $totalSisa = round(array_sum(array_column($rows, 'sisa_pokok')), 2);
        $saldoPiutangLkn = $this->saldoPiutangLkn($spreadsheet);

        $namaUnik = [];
        foreach ($rows as $row) {
            $namaUnik[mb_strtolower($row['nama']).'|'.($row['jenis_kelamin'] ?? '')] = true;
        }

        return [
            'periode' => $this->periodeLaporan($lpp),
            'rows' => $rows,
            'errors' => $errors,
            'ringkasan' => [
                'jumlah_pinjaman' => count($rows),
                'jumlah_aktif' => count(array_filter($rows, fn ($x) => ! $x['lunas'])),
                'jumlah_lunas' => count(array_filter($rows, fn ($x) => $x['lunas'])),
                'jumlah_anggota' => count($namaUnik),
                'total_pokok' => round(array_sum(array_column($rows, 'pokok')), 2),
                'total_sisa_pokok' => $totalSisa,
            ],
            'kontrol' => [
                'sisa_lpp' => $totalSisa,
                'saldo_piutang_lkn' => $saldoPiutangLkn,
                'cocok' => $saldoPiutangLkn === null
                    ? null
                    : abs($totalSisa - $saldoPiutangLkn) < 1,
            ],
        ];
    }

    /**
     * Tulis hasil parse ke database untuk satu desa. Idempoten per no_sppk.
     *
     * @return array{anggota_baru:int, anggota_terhubung:int, pinjaman_baru:int, pinjaman_dilewati:int, angsuran_dibuat:int, sisa_pokok_dijurnal:float, nomor_jurnal:?string}
     */
    public function import(
        int $desaId,
        array $parsed,
        int $unitUsahaId,
        int $akunPiutangId,
        int $akunModalId,
        ?int $userId = null,
    ): array {
        return DB::transaction(function () use ($desaId, $parsed, $unitUsahaId, $akunPiutangId, $akunModalId, $userId) {
            $hasil = [
                'anggota_baru' => 0,
                'anggota_terhubung' => 0,
                'pinjaman_baru' => 0,
                'pinjaman_dilewati' => 0,
                'angsuran_dibuat' => 0,
                'sisa_pokok_dijurnal' => 0.0,
                'nomor_jurnal' => null,
            ];

            $cacheAnggota = [];
            $cacheSektor = [];

            foreach ($parsed['rows'] as $row) {
                $sudahAda = Pinjaman::withoutGlobalScopes()
                    ->where('desa_id', $desaId)
                    ->where('no_sppk', $row['no_sppk'])
                    ->exists();
                if ($sudahAda) {
                    $hasil['pinjaman_dilewati']++;

                    continue;
                }

                $anggota = $this->anggotaUntuk($desaId, $row, $userId, $cacheAnggota, $hasil);
                $sektorId = $this->sektorUntuk($desaId, $row['sektor'], $cacheSektor);

                $pinjaman = Pinjaman::create([
                    'desa_id' => $desaId,
                    'anggota_id' => $anggota->id,
                    'sektor_usaha_id' => $sektorId,
                    'nomor_pinjaman' => sprintf('IMP/%d/%d', $desaId, $row['no_sppk']),
                    'no_sppk' => $row['no_sppk'],
                    'tanggal_pinjaman' => $row['tanggal_pinjaman'],
                    'jumlah_pinjaman' => $row['pokok'],
                    'jangka_waktu_bulan' => $row['tenor'],
                    'jasa_persen' => $row['jasa_persen'],
                    'status_pinjaman' => $row['lunas'] ? 'lunas' : 'aktif',
                    'sumber' => 'import_excel',
                ]);

                $hasil['pinjaman_baru']++;
                $hasil['angsuran_dibuat'] += $this->buatAngsuranSintetis($pinjaman, $row);
                $hasil['sisa_pokok_dijurnal'] += $row['sisa_pokok'];
            }

            $hasil['sisa_pokok_dijurnal'] = round($hasil['sisa_pokok_dijurnal'], 2);

            if ($hasil['sisa_pokok_dijurnal'] > 0) {
                $jurnal = $this->accountingService->createJurnal([
                    'desa_id' => $desaId,
                    'unit_usaha_id' => $unitUsahaId,
                    'tanggal_transaksi' => now()->toDateString(),
                    'jenis_jurnal' => 'memorial',
                    'keterangan' => sprintf(
                        'Saldo awal piutang - impor data historis Excel LPP-UEK%s',
                        $parsed['periode'] ? ' ('.$parsed['periode'].')' : ''
                    ),
                    'status' => 'posted',
                    'details' => [
                        [
                            'akun_id' => $akunPiutangId,
                            'posisi' => 'debit',
                            'jumlah' => $hasil['sisa_pokok_dijurnal'],
                            'keterangan' => 'Saldo awal piutang pinjaman (impor historis)',
                        ],
                        [
                            'akun_id' => $akunModalId,
                            'posisi' => 'kredit',
                            'jumlah' => $hasil['sisa_pokok_dijurnal'],
                            'keterangan' => 'Saldo awal piutang pinjaman (impor historis)',
                        ],
                    ],
                ], allowClosedPeriod: true);

                $hasil['nomor_jurnal'] = $jurnal->nomor_jurnal;
            }

            return $hasil;
        });
    }

    // ------------------------------------------------------------- internal

    private function anggotaUntuk(int $desaId, array $row, ?int $userId, array &$cache, array &$hasil): Anggota
    {
        $kunci = mb_strtolower($row['nama']).'|'.($row['jenis_kelamin'] ?? '');
        if (isset($cache[$kunci])) {
            return $cache[$kunci];
        }

        $anggota = Anggota::withoutGlobalScopes()
            ->where('desa_id', $desaId)
            ->whereRaw('LOWER(nama) = ?', [mb_strtolower($row['nama'])])
            ->first();

        if ($anggota) {
            $hasil['anggota_terhubung']++;
        } else {
            $anggota = Anggota::create([
                'desa_id' => $desaId,
                'nama' => $row['nama'],
                // NIK placeholder unik global: 99 + desa (5 digit) + sppk (9 digit)
                'nik' => sprintf('99%05d%09d', $desaId % 100000, $row['no_sppk'] % 1000000000),
                'nik_sementara' => true,
                'jenis_kelamin' => $row['jenis_kelamin'],
                'tanggal_gabung' => $row['tanggal_pinjaman'],
                'status' => 'aktif',
                'created_by' => $userId,
            ]);
            $hasil['anggota_baru']++;
        }

        return $cache[$kunci] = $anggota;
    }

    private function sektorUntuk(int $desaId, ?string $nama, array &$cache): ?int
    {
        if ($nama === null) {
            return null;
        }
        if (! isset($cache[$nama])) {
            $cache[$nama] = SektorUsaha::withoutGlobalScopes()->firstOrCreate(
                ['desa_id' => $desaId, 'nama' => $nama],
                ['status' => 'aktif'],
            )->id;
        }

        return $cache[$nama];
    }

    /**
     * Pecah total terbayar kumulatif menjadi baris angsuran bulanan sintetis
     * (LPP hanya menyimpan kumulatif, bukan rincian per bulan). Jumlah baris
     * menentukan perhitungan kolektibilitas, jadi harus mendekati jumlah
     * bulan angsuran yang benar-benar dibayar.
     */
    private function buatAngsuranSintetis(Pinjaman $pinjaman, array $row): int
    {
        $totalPokok = $row['bayar_pokok'];
        $totalJasa = $row['bayar_jasa'];
        if ($totalPokok <= 0 && $totalJasa <= 0) {
            return 0;
        }

        $pokokPerBulan = $row['pokok'] / $row['tenor'];
        $n = $row['lunas']
            ? $row['tenor']
            : max(1, min($row['tenor'], (int) round($totalPokok / max($pokokPerBulan, 0.01))));

        $mulai = Carbon::parse($row['tanggal_pinjaman']);
        $sisaP = round($totalPokok, 2);
        $sisaJ = round($totalJasa, 2);

        $baris = [];
        for ($i = 1; $i <= $n; $i++) {
            $p = $i === $n ? $sisaP : round($totalPokok / $n, 2);
            $j = $i === $n ? $sisaJ : round($totalJasa / $n, 2);
            $sisaP = round($sisaP - $p, 2);
            $sisaJ = round($sisaJ - $j, 2);

            $baris[] = [
                'pinjaman_id' => $pinjaman->id,
                'tanggal_bayar' => $mulai->copy()->addMonthsNoOverflow($i)->toDateString(),
                'angsuran_ke' => $i,
                'pokok_dibayar' => $p,
                'jasa_dibayar' => $j,
                'denda_dibayar' => 0,
                'total_dibayar' => round($p + $j, 2),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        AngsuranPinjaman::insert($baris);

        return count($baris);
    }

    private function cariSheet($spreadsheet, string $nama): ?Worksheet
    {
        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            if (strcasecmp(trim($sheetName), $nama) === 0) {
                return $spreadsheet->getSheetByName($sheetName);
            }
        }

        return null;
    }

    private function verifikasiTemplate(Worksheet $lpp): void
    {
        $cek = [
            [self::BARIS_HEADER, self::KOL_NO, 'No'],
            [self::BARIS_HEADER, self::KOL_SPPK, 'No SPPK'],
            [self::BARIS_HEADER, self::KOL_NAMA, 'Nama Pemanfaat'],
            [self::BARIS_HEADER, self::KOL_POKOK, 'Pinjaman (Rp)'],
        ];

        foreach ($cek as [$r, $c, $harap]) {
            $nilai = trim((string) $this->nilai($lpp, $r, $c));
            if (strcasecmp($nilai, $harap) !== 0) {
                throw new \RuntimeException(sprintf(
                    'Template tidak sesuai: sel baris %d kolom %d seharusnya "%s", terbaca "%s". '.
                    'File ini tampaknya bukan template LPP-UEK standar kabupaten.',
                    $r, $c, $harap, $nilai
                ));
            }
        }
    }

    private function periodeLaporan(Worksheet $lpp): ?string
    {
        // Baris 4: "Bulan" | "" | ": APRIL 2018"
        $v = trim((string) $this->nilai($lpp, 4, 3));

        return $v !== '' ? ltrim($v, ': ') : null;
    }

    private function saldoPiutangLkn($spreadsheet): ?float
    {
        $lkn = $this->cariSheet($spreadsheet, 'LKN I');
        if (! $lkn) {
            return null;
        }

        $maxRow = min(90, $lkn->getHighestRow());
        for ($r = 1; $r <= $maxRow; $r++) {
            $kode = $this->angka($lkn, $r, 1);
            $nama = (string) $this->nilai($lkn, $r, 2);
            if ($kode === 13.0 && stripos($nama, 'Piutang') !== false) {
                return $this->angka($lkn, $r, 7); // kolom G = saldo akhir debet
            }
        }

        return null;
    }

    private function nilai(Worksheet $sheet, int $row, int $col)
    {
        $cell = $sheet->getCell([$col, $row]);
        $v = $cell->getValue();

        // Sel berumus: pakai hasil ter-cache dari Excel, JANGAN hitung ulang
        // (mesin kalkulasi sangat lambat dan rumusnya merujuk sheet yang
        // sengaja tidak dimuat).
        if (is_string($v) && str_starts_with($v, '=')) {
            return $cell->getOldCalculatedValue();
        }

        return $v;
    }

    private function angka(Worksheet $sheet, int $row, int $col): ?float
    {
        $v = $this->nilai($sheet, $row, $col);
        if (is_numeric($v)) {
            return (float) $v;
        }

        return null;
    }

    private function tanggal(Worksheet $sheet, int $row, int $col): ?Carbon
    {
        $v = $this->nilai($sheet, $row, $col);
        if (is_numeric($v) && $v > 1000) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v));
            } catch (\Throwable) {
                return null;
            }
        }
        if (is_string($v) && trim($v) !== '') {
            try {
                return Carbon::parse(trim($v));
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
