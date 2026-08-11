{{-- Kop laporan standar. Param: $judul, $periode, $desa (opsional $unitUsaha) --}}
@php($pengaturan = \App\Models\Pengaturan::getSettings())
<table style="width:100%; border-collapse:collapse; border-bottom:3px double #333; margin-bottom:10px;">
    <tr>
        <td style="border:none; padding:0 0 6px 0;">
            <div style="font-size:14px; font-weight:bold;">{{ $pengaturan->nama_instansi ?? 'SIPKUD' }}</div>
            <div style="font-size:11px;">{{ $pengaturan->nama_daerah ?? '' }}</div>
            @if(! empty($pengaturan->alamat))
                <div style="font-size:9px; color:#555;">{{ $pengaturan->alamat }}</div>
            @endif
        </td>
        <td style="border:none; text-align:right; font-size:9px; color:#555; vertical-align:top;">
            Dicetak: {{ now()->translatedFormat('d F Y H:i') }}
        </td>
    </tr>
</table>
<div style="text-align:center; margin-bottom:12px;">
    <div style="font-size:13px; font-weight:bold; text-transform:uppercase;">{{ $judul }}</div>
    <div style="font-size:11px;">{{ $desa->nama_desa ?? '' }}@isset($desa->kecamatan) - Kecamatan {{ $desa->kecamatan->nama_kecamatan }}@endisset{{ ! empty($unitUsaha) ? ' - '.$unitUsaha->nama_unit : '' }}</div>
    <div style="font-size:11px;">{{ $periode }}</div>
    <div style="font-size:9px; color:#555;">(Disajikan dalam Rupiah)</div>
</div>
