<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Anggota</title>
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
        
        .status-nonaktif {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>DATA MASTER ANGGOTA USP/UED-SP</h1>
        @if($kecamatanNama)
            <div class="info">Kecamatan: {{ $kecamatanNama }}</div>
        @endif
        @if($desaNama)
            <div class="info">Desa: {{ $desaNama }}</div>
        @endif
        @if($kelompokNama)
            <div class="info">Kelompok: {{ $kelompokNama }}</div>
        @endif
        @if($statusFilter)
            <div class="info">Status: {{ ucfirst($statusFilter) }}</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th width="3%" class="text-center">No</th>
                <th width="10%">NIK</th>
                <th width="15%">Nama</th>
                <th width="10%">Kelompok</th>
                @if($user->hasKabupatenScope())
                    <th width="10%">Kecamatan</th>
                    <th width="10%">Desa</th>
                @endif
                <th width="15%">Alamat</th>
                <th width="8%">No HP</th>
                <th width="5%" class="text-center">JK</th>
                <th width="7%" class="text-center">Tgl Gabung</th>
                <th width="7%" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @forelse($anggota as $item)
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td>{{ $item->nik ?? '-' }}</td>
                    <td>{{ $item->nama }}</td>
                    <td>{{ $item->kelompok->nama_kelompok ?? '-' }}</td>
                    @if($user->hasKabupatenScope())
                        <td>{{ $item->desa->kecamatan->nama_kecamatan ?? '-' }}</td>
                        <td>{{ $item->desa->nama_desa ?? '-' }}</td>
                    @endif
                    <td>{{ $item->alamat ?? '-' }}</td>
                    <td>{{ $item->nomor_hp ?? '-' }}</td>
                    <td class="text-center">{{ $item->jenis_kelamin === 'L' ? 'L' : ($item->jenis_kelamin === 'P' ? 'P' : '-') }}</td>
                    <td class="text-center">{{ $item->tanggal_gabung ? $item->tanggal_gabung->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">
                        <span class="status-badge status-{{ $item->status }}">
                            {{ ucfirst($item->status) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $user->hasKabupatenScope() ? '11' : '9' }}" class="text-center">
                        Tidak ada data anggota ditemukan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Total: {{ $anggota->count() }} anggota | Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}
    </div>
</body>
</html>

