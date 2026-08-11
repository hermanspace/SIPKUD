<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Akhir USP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.6;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .header .info {
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .section {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #4f81bd;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #4f81bd;
        }
        
        .row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .row:last-child {
            border-bottom: none;
        }
        
        .row.total {
            font-weight: bold;
            background-color: #e8f4ff;
            padding: 10px;
            margin-top: 8px;
            border-radius: 3px;
        }
        
        .label {
            flex: 1;
            font-weight: 500;
        }
        
        .value {
            font-weight: 600;
            text-align: right;
            min-width: 150px;
        }
        
        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #666;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN AKHIR USP</h1>
        @if($periode)
            <div class="info">Periode: {{ $periode }}</div>
        @endif
        @if($kecamatanNama)
            <div class="info">Kecamatan: {{ $kecamatanNama }}</div>
        @endif
        @if($desaNama)
            <div class="info">Desa: {{ $desaNama }}</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">PENDAPATAN</div>
        <div class="row">
            <div class="label">Pendapatan Jasa</div>
            <div class="value">Rp {{ number_format($totalPendapatanJasa, 0, ',', '.') }}</div>
        </div>
        <div class="row">
            <div class="label">Pendapatan Denda</div>
            <div class="value">Rp {{ number_format($totalPendapatanDenda, 0, ',', '.') }}</div>
        </div>
        <div class="row total">
            <div class="label">Total Pendapatan</div>
            <div class="value">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">SISA HASIL USAHA (SHU)</div>
        <div class="row">
            <div class="label">Total Beban</div>
            <div class="value">(Rp {{ number_format($totalBeban, 0, ',', '.') }})</div>
        </div>
        <div class="row">
            <div class="label">SHU / Laba Bersih (Pendapatan - Beban)</div>
            <div class="value">Rp {{ number_format($totalShu, 0, ',', '.') }}</div>
        </div>
        @foreach($alokasiShu as $item)
            <div class="row">
                <div class="label" style="padding-left: 12px">Alokasi: {{ $item['nama'] }} ({{ $item['persen'] }}%)</div>
                <div class="value">Rp {{ number_format($item['jumlah'], 0, ',', '.') }}</div>
            </div>
        @endforeach
    </div>

    <div class="section">
        <div class="section-title">PINJAMAN</div>
        <div class="row">
            <div class="label">Total Pinjaman Tersalurkan</div>
            <div class="value">Rp {{ number_format($totalPinjamanTersalurkan, 0, ',', '.') }}</div>
        </div>
        <div class="row">
            <div class="label">Total Pokok Terbayar</div>
            <div class="value">Rp {{ number_format($totalPokokTerbayar, 0, ',', '.') }}</div>
        </div>
        <div class="row">
            <div class="label">Jumlah Pinjaman Aktif</div>
            <div class="value">{{ $jumlahPinjamanAktif }} pinjaman</div>
        </div>
        <div class="row total">
            <div class="label">Total Sisa Pinjaman</div>
            <div class="value">Rp {{ number_format($totalSisaPinjaman, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="footer">
        Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}
    </div>
</body>
</html>

