<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Pinjaman</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 9px;
            line-height: 1.4;
            padding: 15px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .header .info {
            font-size: 11px;
            margin-bottom: 3px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        table th {
            background-color: #4f81bd;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #2d5a8b;
            font-size: 9px;
        }
        
        table td {
            padding: 6px 5px;
            border: 1px solid #ddd;
            font-size: 9px;
        }
        
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #e8f4ff !important;
        }
        
        .footer {
            margin-top: 20px;
            font-size: 8px;
            color: #666;
            text-align: right;
        }
        
        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .status-aktif {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-lunas {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-nunggak {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>DATA MASTER PINJAMAN USP/UED-SP</h1>
        @if($kecamatanNama)
            <div class="info">Kecamatan: {{ $kecamatanNama }}</div>
        @endif
        @if($desaNama)
            <div class="info">Desa: {{ $desaNama }}</div>
        @endif
        @if($statusFilter)
            <div class="info">Status: {{ ucfirst($statusFilter) }}</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th width="3%" class="text-center">No</th>
                <th width="13%">No. Pinjaman</th>
                <th width="8%" class="text-center">Tanggal</th>
                <th width="18%">Nama Anggota</th>
                @if($user->hasKabupatenScope())
                    <th width="15%">Desa</th>
                @endif
                <th width="13%" class="text-right">Jumlah Pinjaman</th>
                <th width="10%" class="text-center">Jangka Waktu</th>
                <th width="8%" class="text-center">Jasa (%)</th>
                <th width="12%" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $no = 1;
                $totalJumlahPinjaman = 0;
            @endphp
            @forelse($pinjaman as $item)
                @php
                    $totalJumlahPinjaman += $item->jumlah_pinjaman;
                    
                    $statusLabel = match($item->status_pinjaman) {
                        'aktif' => 'Aktif',
                        'lunas' => 'Lunas',
                        'nunggak' => 'Nunggak',
                        default => ucfirst($item->status_pinjaman)
                    };
                    
                    $statusClass = match($item->status_pinjaman) {
                        'aktif' => 'status-aktif',
                        'lunas' => 'status-lunas',
                        'nunggak' => 'status-nunggak',
                        default => ''
                    };
                @endphp
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td>{{ $item->nomor_pinjaman }}</td>
                    <td class="text-center">{{ $item->tanggal_pinjaman->format('d/m/Y') }}</td>
                    <td>{{ $item->anggota->nama ?? '-' }}</td>
                    @if($user->hasKabupatenScope())
                        <td>{{ $item->desa->nama_desa ?? '-' }}</td>
                    @endif
                    <td class="text-right">{{ number_format($item->jumlah_pinjaman, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $item->jangka_waktu_bulan }} bulan</td>
                    <td class="text-center">{{ number_format($item->jasa_persen, 2) }}</td>
                    <td class="text-center">
                        <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $user->hasKabupatenScope() ? '9' : '8' }}" class="text-center">
                        Tidak ada data pinjaman ditemukan.
                    </td>
                </tr>
            @endforelse
            
            @if($pinjaman->count() > 0)
                <tr class="total-row">
                    <td colspan="{{ $user->hasKabupatenScope() ? '5' : '4' }}" class="text-right">TOTAL:</td>
                    <td class="text-right">{{ number_format($totalJumlahPinjaman, 0, ',', '.') }}</td>
                    <td colspan="3"></td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Total: {{ $pinjaman->count() }} pinjaman | Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}
    </div>
</body>
</html>

