<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { text-align: center; font-size: 16px; margin: 4px 0; }
        h2 { text-align: center; font-size: 13px; margin: 4px 0 10px 0; }
        h3 { font-size: 12px; border-bottom: 1px solid #333; padding-bottom: 3px; margin: 14px 0 6px 0; }
        table { width: 100%; border-collapse: collapse; margin: 6px 0; }
        td, th { padding: 3px 5px; }
        .bordered td, .bordered th { border: 1px solid #999; }
        th { background: #eee; }
        .right { text-align: right; }
        .head { background: #eee; font-weight: bold; }
        .total { border-top: 1px solid #333; font-weight: bold; }
        .break { page-break-before: always; }
        .cover { text-align: center; margin-top: 180px; }
    </style>
</head>
<body>
    {{-- Sampul --}}
    <div class="cover">
        <h1>LAPORAN KEUANGAN TAHUNAN</h1>
        <h2>{{ $desa->nama_desa ?? '' }} - Kecamatan {{ $desa->kecamatan->nama_kecamatan ?? '-' }}</h2>
        <h2>Tahun Buku {{ $tahun }}</h2>
        <p style="margin-top:30px">Disusun sesuai PP No. 11 Tahun 2021 dan Kepmendesa PDTT No. 136 Tahun 2022</p>
        <p>Status tahun buku: {{ $tahunDitutup ? 'SUDAH DITUTUP' : 'BELUM DITUTUP' }}</p>
    </div>

    {{-- 1. Laba Rugi --}}
    <div class="break"></div>
    <h3>1. LAPORAN LABA RUGI - Tahun {{ $tahun }}</h3>
    <table>
        <tr class="head"><td colspan="2">PENDAPATAN</td></tr>
        @foreach($labaRugi['detail_pendapatan'] as $p)
            <tr><td style="padding-left:16px">{{ $p['kode_akun'] }} {{ $p['nama_akun'] }}</td><td class="right">{{ number_format($p['jumlah'], 2, ',', '.') }}</td></tr>
        @endforeach
        <tr class="total"><td>Total Pendapatan</td><td class="right">{{ number_format($labaRugi['pendapatan'], 2, ',', '.') }}</td></tr>
        <tr class="head"><td colspan="2">BEBAN</td></tr>
        @foreach($labaRugi['detail_beban'] as $b)
            <tr><td style="padding-left:16px">{{ $b['kode_akun'] }} {{ $b['nama_akun'] }}</td><td class="right">({{ number_format($b['jumlah'], 2, ',', '.') }})</td></tr>
        @endforeach
        <tr class="total"><td>Total Beban</td><td class="right">({{ number_format($labaRugi['beban'], 2, ',', '.') }})</td></tr>
        <tr class="head total"><td>LABA (RUGI) BERSIH / SHU</td><td class="right">{{ number_format($labaRugi['laba_bersih'], 2, ',', '.') }}</td></tr>
    </table>

    {{-- 2. Perubahan Ekuitas --}}
    <h3>2. LAPORAN PERUBAHAN EKUITAS - per 31 Desember {{ $tahun }}</h3>
    <table>
        <tr><td>Modal Awal</td><td class="right">{{ number_format($perubahanModal['modal_awal'], 2, ',', '.') }}</td></tr>
        <tr><td>Laba (Rugi) Bersih Berjalan</td><td class="right">{{ number_format($perubahanModal['laba_bersih'], 2, ',', '.') }}</td></tr>
        <tr><td>Prive / Pengambilan</td><td class="right">{{ number_format($perubahanModal['prive'], 2, ',', '.') }}</td></tr>
        <tr class="head total"><td>MODAL AKHIR</td><td class="right">{{ number_format($perubahanModal['modal_akhir'], 2, ',', '.') }}</td></tr>
    </table>

    {{-- 3. Neraca --}}
    <div class="break"></div>
    <h3>3. LAPORAN POSISI KEUANGAN (NERACA) - per 31 Desember {{ $tahun }}</h3>
    <table>
        @foreach(['aset' => 'ASET', 'kewajiban' => 'KEWAJIBAN', 'modal' => 'EKUITAS'] as $key => $judul)
            <tr class="head"><td colspan="2">{{ $judul }}</td></tr>
            @foreach(($neraca['detail_'.$key] ?? []) as $row)
                <tr>
                    <td style="padding-left:16px">{{ $row['kode_akun'] ?? '' }} {{ $row['nama_akun'] ?? '' }}</td>
                    <td class="right">{{ number_format($row['saldo'] ?? 0, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td>Total {{ ucfirst($key) }}</td>
                <td class="right">{{ number_format($neraca[$key] ?? 0, 2, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr class="head total">
            <td>TOTAL KEWAJIBAN + EKUITAS {{ ($neraca['is_balanced'] ?? false) ? '(seimbang dengan aset)' : '(TIDAK SEIMBANG)' }}</td>
            <td class="right">{{ number_format($neraca['total_kewajiban_modal'] ?? 0, 2, ',', '.') }}</td>
        </tr>
    </table>

    {{-- 4. Arus Kas --}}
    <div class="break"></div>
    <h3>4. LAPORAN ARUS KAS - Tahun {{ $tahun }}</h3>
    <table>
        @foreach(['operasi' => 'AKTIVITAS OPERASI', 'investasi' => 'AKTIVITAS INVESTASI', 'pendanaan' => 'AKTIVITAS PENDANAAN'] as $key => $judul)
            <tr class="head"><td colspan="2">{{ $judul }}</td></tr>
            @foreach($arusKas['aktivitas'][$key]['masuk'] as $row)
                <tr><td style="padding-left:16px">Penerimaan - {{ $row['nama_akun'] }}</td><td class="right">{{ number_format($row['jumlah'], 2, ',', '.') }}</td></tr>
            @endforeach
            @foreach($arusKas['aktivitas'][$key]['keluar'] as $row)
                <tr><td style="padding-left:16px">Pengeluaran - {{ $row['nama_akun'] }}</td><td class="right">({{ number_format($row['jumlah'], 2, ',', '.') }})</td></tr>
            @endforeach
            <tr class="total"><td>Kas neto dari {{ strtolower($judul) }}</td><td class="right">{{ number_format($arusKas['aktivitas'][$key]['neto'], 2, ',', '.') }}</td></tr>
        @endforeach
        <tr class="total"><td>KENAIKAN (PENURUNAN) KAS</td><td class="right">{{ number_format($arusKas['kenaikan_kas'], 2, ',', '.') }}</td></tr>
        <tr><td>Saldo kas awal tahun</td><td class="right">{{ number_format($arusKas['saldo_awal_kas'], 2, ',', '.') }}</td></tr>
        <tr class="head"><td>SALDO KAS AKHIR TAHUN</td><td class="right">{{ number_format($arusKas['saldo_akhir_kas'], 2, ',', '.') }}</td></tr>
    </table>

    {{-- 5. CALK --}}
    <div class="break"></div>
    <h3>5. CATATAN ATAS LAPORAN KEUANGAN (CALK)</h3>

    <p><strong>a. Umum.</strong> Laporan keuangan ini disusun untuk unit usaha simpan pinjam (USP)
    {{ $desa->nama_desa ?? '' }} untuk tahun buku {{ $tahun }}.</p>

    <p><strong>b. Ikhtisar Kebijakan Akuntansi.</strong> Pembukuan double entry dengan periode bulanan terkunci.
    Pendapatan jasa &amp; denda diakui saat kas diterima (basis kas - penyederhanaan dari basis akrual SAK EMKM).
    Piutang disajikan sebesar sisa pokok dikurangi cadangan kerugian piutang; penyisihan berdasar kolektibilitas
    (lancar {{ config('accounting.penyisihan.lancar') }}%, kurang lancar {{ config('accounting.penyisihan.kurang_lancar') }}%,
    diragukan {{ config('accounting.penyisihan.diragukan') }}%, macet {{ config('accounting.penyisihan.macet') }}%).
    SHU = laba bersih; alokasi sesuai AD/ART: {{ collect($alokasiShu)->map(fn ($a) => $a['nama'].' '.$a['persen'].'%')->implode(', ') }}.</p>

    <p><strong>c. Kualitas Piutang.</strong> Total sisa pokok pinjaman aktif
    Rp {{ number_format($kolektibilitas['total_sisa'], 2, ',', '.') }}; NPL {{ number_format($kolektibilitas['npl_persen'], 2, ',', '.') }}%.</p>
    <table class="bordered">
        <thead><tr><th>Kolektibilitas</th><th>Jumlah</th><th>Sisa Pokok</th><th>Penyisihan</th></tr></thead>
        <tbody>
            @foreach($kolektibilitas['kategori'] as $nama => $k)
                <tr>
                    <td>{{ str_replace('_', ' ', $nama) }}</td>
                    <td class="right">{{ $k['jumlah'] }}</td>
                    <td class="right">{{ number_format($k['sisa'], 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($k['penyisihan'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p><strong>d. Rincian Saldo Akun (per 31 Desember {{ $tahun }}).</strong></p>
    <table class="bordered">
        <thead><tr><th>Kode</th><th>Akun</th><th>Debit</th><th>Kredit</th></tr></thead>
        <tbody>
            @foreach($neracaSaldo as $row)
                <tr>
                    <td>{{ $row['kode_akun'] }}</td>
                    <td>{{ $row['nama_akun'] }}</td>
                    <td class="right">{{ number_format($row['saldo_akhir_debit'], 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($row['saldo_akhir_kredit'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
