<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h2, h3 { text-align: center; margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        td { padding: 3px 6px; }
        .head { background: #eee; font-weight: bold; }
        .right { text-align: right; }
        .total { border-top: 1px solid #333; font-weight: bold; }
    </style>
</head>
<body>
    <h2>LAPORAN ARUS KAS</h2>
    <h3>{{ $desa->nama_desa ?? '' }}{{ $unitUsaha ? ' - '.$unitUsaha->nama_unit : '' }}</h3>
    <h3>Periode: {{ $periode }}</h3>

    <table>
        @foreach([
            'operasi' => 'ARUS KAS DARI AKTIVITAS OPERASI',
            'investasi' => 'ARUS KAS DARI AKTIVITAS INVESTASI',
            'pendanaan' => 'ARUS KAS DARI AKTIVITAS PENDANAAN',
        ] as $key => $judul)
            <tr class="head"><td colspan="2">{{ $judul }}</td></tr>
            @foreach($data['aktivitas'][$key]['masuk'] as $row)
                <tr><td style="padding-left:20px">Penerimaan - {{ $row['nama_akun'] }}</td><td class="right">{{ number_format($row['jumlah'], 2, ',', '.') }}</td></tr>
            @endforeach
            @foreach($data['aktivitas'][$key]['keluar'] as $row)
                <tr><td style="padding-left:20px">Pengeluaran - {{ $row['nama_akun'] }}</td><td class="right">({{ number_format($row['jumlah'], 2, ',', '.') }})</td></tr>
            @endforeach
            <tr class="total"><td>Kas neto dari aktivitas {{ $key }}</td><td class="right">{{ number_format($data['aktivitas'][$key]['neto'], 2, ',', '.') }}</td></tr>
        @endforeach
        <tr class="total"><td>KENAIKAN (PENURUNAN) KAS</td><td class="right">{{ number_format($data['kenaikan_kas'], 2, ',', '.') }}</td></tr>
        <tr><td>Saldo kas awal periode</td><td class="right">{{ number_format($data['saldo_awal_kas'], 2, ',', '.') }}</td></tr>
        <tr class="head"><td>SALDO KAS AKHIR PERIODE</td><td class="right">{{ number_format($data['saldo_akhir_kas'], 2, ',', '.') }}</td></tr>
    </table>
</body>
</html>
