{{-- Isi CALK - dipakai halaman web dan PDF --}}
<div class="space-y-6 text-sm leading-relaxed">
    <section>
        <h3 class="font-semibold text-base mb-2">1. Umum</h3>
        <p>
            Laporan keuangan ini disusun untuk unit usaha simpan pinjam (USP)
            {{ $desa->nama_desa ?? '' }}, Kecamatan {{ $desa->kecamatan->nama_kecamatan ?? '-' }},
            untuk tahun buku {{ $tahun }}.
            @if($units->isNotEmpty())
                Unit usaha yang tercakup: {{ $units->pluck('nama_unit')->implode(', ') }}.
            @endif
            Status tahun buku: <strong>{{ $tahunDitutup ? 'sudah ditutup (jurnal penutup dibukukan)' : 'belum ditutup' }}</strong>.
        </p>
    </section>

    <section>
        <h3 class="font-semibold text-base mb-2">2. Ikhtisar Kebijakan Akuntansi</h3>
        <ul class="list-disc ml-5 space-y-1">
            <li>Pembukuan diselenggarakan dengan sistem berpasangan (double entry) dan periode akuntansi bulanan yang dikunci setelah tutup periode.</li>
            <li>Pendapatan jasa pinjaman dan denda <strong>diakui pada saat kas diterima (basis kas)</strong>; beban diakui pada saat dibayarkan. Hal ini merupakan penyederhanaan dari basis akrual SAK EMKM dan diungkapkan sebagai kebijakan entitas.</li>
            <li>Piutang pinjaman anggota disajikan sebesar sisa pokok pinjaman, dikurangi cadangan kerugian piutang.</li>
            <li>Penyisihan kerugian piutang dibentuk berdasarkan kolektibilitas pinjaman (lancar {{ config('accounting.penyisihan.lancar') }}%, kurang lancar {{ config('accounting.penyisihan.kurang_lancar') }}%, diragukan {{ config('accounting.penyisihan.diragukan') }}%, macet {{ config('accounting.penyisihan.macet') }}%) mengikuti konvensi penilaian kesehatan KSP/USP.</li>
            <li>Sisa Hasil Usaha (SHU) adalah laba bersih tahun berjalan; alokasi SHU mengikuti AD/ART:
                {{ collect($alokasiShu)->map(fn ($a) => $a['nama'].' '.$a['persen'].'%')->implode(', ') }}.</li>
        </ul>
    </section>

    <section>
        <h3 class="font-semibold text-base mb-2">3. Rincian Saldo Akun Material (per Desember {{ $tahun }})</h3>
        <div class="overflow-x-auto">
        <table class="min-w-full border text-xs">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="border px-2 py-1 text-left">Kode</th>
                    <th class="border px-2 py-1 text-left">Akun</th>
                    <th class="border px-2 py-1 text-right">Saldo Debit</th>
                    <th class="border px-2 py-1 text-right">Saldo Kredit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($neracaSaldo as $row)
                    <tr>
                        <td class="border px-2 py-1">{{ $row['kode_akun'] }}</td>
                        <td class="border px-2 py-1">{{ $row['nama_akun'] }}</td>
                        <td class="border px-2 py-1 text-right">{{ number_format($row['saldo_akhir_debit'], 2, ',', '.') }}</td>
                        <td class="border px-2 py-1 text-right">{{ number_format($row['saldo_akhir_kredit'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </section>

    <section>
        <h3 class="font-semibold text-base mb-2">4. Piutang Pinjaman dan Kualitasnya</h3>
        <p class="mb-2">
            Jumlah pinjaman aktif: {{ $jumlahPinjamanAktif }} pinjaman dengan total sisa pokok
            Rp {{ number_format($kolektibilitas['total_sisa'], 2, ',', '.') }}.
            Rasio pinjaman bermasalah (NPL): {{ number_format($kolektibilitas['npl_persen'], 2, ',', '.') }}%.
        </p>
        <div class="overflow-x-auto">
        <table class="min-w-full border text-xs">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="border px-2 py-1 text-left">Kolektibilitas</th>
                    <th class="border px-2 py-1 text-right">Jumlah Pinjaman</th>
                    <th class="border px-2 py-1 text-right">Sisa Pokok</th>
                    <th class="border px-2 py-1 text-right">Penyisihan ({{ '%' }})</th>
                    <th class="border px-2 py-1 text-right">Nilai Penyisihan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kolektibilitas['kategori'] as $nama => $k)
                    <tr>
                        <td class="border px-2 py-1 capitalize">{{ str_replace('_', ' ', $nama) }}</td>
                        <td class="border px-2 py-1 text-right">{{ $k['jumlah'] }}</td>
                        <td class="border px-2 py-1 text-right">{{ number_format($k['sisa'], 2, ',', '.') }}</td>
                        <td class="border px-2 py-1 text-right">{{ $k['rate'] }}%</td>
                        <td class="border px-2 py-1 text-right">{{ number_format($k['penyisihan'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </section>

    <section>
        <h3 class="font-semibold text-base mb-2">5. Pendapatan dan Beban Tahun Berjalan</h3>
        <p>
            Total pendapatan kumulatif: Rp {{ number_format($labaRugi['pendapatan'], 2, ',', '.') }};
            total beban kumulatif: Rp {{ number_format($labaRugi['beban'], 2, ',', '.') }};
            laba (rugi) bersih: <strong>Rp {{ number_format($labaRugi['laba_bersih'], 2, ',', '.') }}</strong>.
            Rincian per akun disajikan pada Laporan Laba Rugi.
        </p>
    </section>
</div>
