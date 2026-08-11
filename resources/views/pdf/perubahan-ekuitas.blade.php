<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h2, h3 { text-align: center; margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        td { padding: 4px 6px; }
        .head { background: #eee; font-weight: bold; }
        .right { text-align: right; }
        .total { border-top: 1px solid #333; font-weight: bold; }
    </style>
</head>
<body>
    <h2>LAPORAN PERUBAHAN EKUITAS</h2>
    <h3>{{ $desa->nama_desa ?? '' }}{{ $unitUsaha ? ' - '.$unitUsaha->nama_unit : '' }}</h3>
    <h3>Periode: {{ $periode }}</h3>

    <table>
        <tr><td>Modal Awal</td><td class="right">{{ number_format($data['modal_awal'], 2, ',', '.') }}</td></tr>
        <tr><td>Laba (Rugi) Bersih Berjalan</td><td class="right">{{ number_format($data['laba_bersih'], 2, ',', '.') }}</td></tr>
        @foreach($data['detail_prive'] as $prive)
            <tr><td style="padding-left:20px">{{ $prive['nama_akun'] }}</td><td class="right">{{ number_format($prive['saldo'], 2, ',', '.') }}</td></tr>
        @endforeach
        <tr><td>Prive / Pengambilan</td><td class="right">{{ number_format($data['prive'], 2, ',', '.') }}</td></tr>
        <tr class="head total"><td>MODAL AKHIR</td><td class="right">{{ number_format($data['modal_akhir'], 2, ',', '.') }}</td></tr>
    </table>
</body>
</html>
