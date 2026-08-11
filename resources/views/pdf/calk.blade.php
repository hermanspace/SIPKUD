<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h2, h3.center { text-align: center; margin: 2px 0; }
        h3 { margin: 12px 0 4px 0; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 6px 0; }
        th, td { border: 1px solid #999; padding: 3px 5px; }
        th { background: #eee; }
        .right { text-align: right; }
        ul { margin: 4px 0; padding-left: 16px; }
    </style>
</head>
<body>
    @include('pdf.partials.kop', ['judul' => 'Catatan atas Laporan Keuangan', 'periode' => 'Tahun Buku '.$tahun, 'desa' => $desa])

    <h3>1. Umum</h3>
    <p>Laporan keuangan ini disusun untuk unit usaha simpan pinjam (USP) {{ $desa->nama_desa ?? '' }},
    Kecamatan {{ $desa->kecamatan->nama_kecamatan ?? '-' }}, tahun buku {{ $tahun }}.
    Status tahun buku: {{ $tahunDitutup ? 'sudah ditutup' : 'belum ditutup' }}.</p>

    <h3>2. Ikhtisar Kebijakan Akuntansi</h3>
    <ul>
        <li>Pembukuan double entry, periode bulanan dikunci setelah tutup periode.</li>
        <li>Pendapatan jasa &amp; denda diakui saat kas diterima (basis kas) - penyederhanaan dari basis akrual SAK EMKM.</li>
        <li>Piutang pinjaman disajikan sebesar sisa pokok dikurangi cadangan kerugian piutang.</li>
        <li>Penyisihan kerugian piutang berdasar kolektibilitas: lancar {{ config('accounting.penyisihan.lancar') }}%, kurang lancar {{ config('accounting.penyisihan.kurang_lancar') }}%, diragukan {{ config('accounting.penyisihan.diragukan') }}%, macet {{ config('accounting.penyisihan.macet') }}%.</li>
        <li>SHU = laba bersih tahun berjalan; alokasi sesuai AD/ART: {{ collect($alokasiShu)->map(fn ($a) => $a['nama'].' '.$a['persen'].'%')->implode(', ') }}.</li>
    </ul>

    <h3>3. Rincian Saldo Akun Material (per Desember {{ $tahun }})</h3>
    <table>
        <thead><tr><th>Kode</th><th>Akun</th><th>Saldo Debit</th><th>Saldo Kredit</th></tr></thead>
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

    <h3>4. Piutang Pinjaman dan Kualitasnya</h3>
    <p>Pinjaman aktif: {{ $jumlahPinjamanAktif }}; total sisa pokok Rp {{ number_format($kolektibilitas['total_sisa'], 2, ',', '.') }};
    NPL {{ number_format($kolektibilitas['npl_persen'], 2, ',', '.') }}%.</p>
    <table>
        <thead><tr><th>Kolektibilitas</th><th>Jumlah</th><th>Sisa Pokok</th><th>%</th><th>Penyisihan</th></tr></thead>
        <tbody>
            @foreach($kolektibilitas['kategori'] as $nama => $k)
                <tr>
                    <td>{{ str_replace('_', ' ', $nama) }}</td>
                    <td class="right">{{ $k['jumlah'] }}</td>
                    <td class="right">{{ number_format($k['sisa'], 2, ',', '.') }}</td>
                    <td class="right">{{ $k['rate'] }}%</td>
                    <td class="right">{{ number_format($k['penyisihan'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>5. Pendapatan dan Beban Tahun Berjalan</h3>
    <p>Total pendapatan kumulatif Rp {{ number_format($labaRugi['pendapatan'], 2, ',', '.') }};
    total beban kumulatif Rp {{ number_format($labaRugi['beban'], 2, ',', '.') }};
    laba (rugi) bersih <strong>Rp {{ number_format($labaRugi['laba_bersih'], 2, ',', '.') }}</strong>.</p>
    @include('pdf.partials.ttd')
</body>
</html>
