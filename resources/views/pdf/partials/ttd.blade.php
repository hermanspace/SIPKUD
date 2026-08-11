{{-- Blok tanda tangan standar laporan keuangan --}}
<table style="width:100%; border-collapse:collapse; margin-top:36px; page-break-inside:avoid;">
    <tr>
        <td style="border:none; width:50%; text-align:center; font-size:10px;">
            Disusun oleh,<br>Bendahara / Pengelola USP
            <div style="margin-top:60px;">( ................................ )</div>
        </td>
        <td style="border:none; width:50%; text-align:center; font-size:10px;">
            {{ ($desa->nama_desa ?? '') }}, {{ now()->translatedFormat('d F Y') }}<br>Mengetahui,<br>Kepala Desa / Penasihat
            <div style="margin-top:47px;">( ................................ )</div>
        </td>
    </tr>
</table>
