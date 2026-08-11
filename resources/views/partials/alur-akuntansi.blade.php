{{-- Flowchart alur proses akuntansi SIPKUD - tampil untuk semua level akses --}}
<div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
    <flux:heading size="lg" class="mb-1">Alur Proses Akuntansi SIPKUD</flux:heading>
    <p class="text-sm text-zinc-500 mb-6">Dari transaksi harian di desa hingga pembinaan di tingkat kabupaten</p>

    @php
        $lanes = [
            [
                'role' => 'Admin Desa',
                'border' => 'border-emerald-200 dark:border-emerald-900', 'header' => 'bg-emerald-600', 'body' => 'bg-emerald-50/50 dark:bg-zinc-900', 'badge' => 'bg-emerald-600', 'line' => 'bg-emerald-300 dark:bg-emerald-800',
                'ikon' => 'M12 6v12m-8-6h16',
                'steps' => [
                    ['Input Master Data', 'Kelompok, anggota, unit usaha, saldo awal kas'],
                    ['Catat Transaksi', 'Pinjaman & angsuran, kas harian, buku memorial - jurnal dibuat otomatis (debit = kredit)'],
                    ['Penyesuaian Berkala', 'Penyisihan piutang (kolektibilitas) & penyusutan aset tetap otomatis'],
                    ['Tutup Periode Bulanan', 'Kunci periode - transaksi terkunci tidak bisa diubah'],
                    ['Tutup Buku Tahunan', 'Jurnal penutup: laba -> SHU, siapkan Laporan Tahunan untuk Musdes'],
                ],
            ],
            [
                'role' => 'Admin Kecamatan',
                'border' => 'border-sky-200 dark:border-sky-900', 'header' => 'bg-sky-600', 'body' => 'bg-sky-50/50 dark:bg-zinc-900', 'badge' => 'bg-sky-600', 'line' => 'bg-sky-300 dark:bg-sky-800',
                'ikon' => 'M2.25 21h19.5M3.75 21V9.349m16.5 11.651V9.349',
                'steps' => [
                    ['Pantau Seluruh Desa', 'Akses baca semua data desa di wilayah kecamatannya'],
                    ['Review Laporan', 'Neraca, Laba Rugi, Arus Kas, LPP UED per desa - bandingkan antar desa'],
                    ['Pantau Kesehatan USP', 'Laporan Kolektibilitas & NPL - deteksi dini pinjaman bermasalah'],
                    ['Bina & Verifikasi', 'Kelola akun pengguna desa, master akun (COA), pembinaan pembukuan'],
                ],
            ],
            [
                'role' => 'Kabupaten (Super Admin / PMD)',
                'border' => 'border-violet-200 dark:border-violet-900', 'header' => 'bg-violet-600', 'body' => 'bg-violet-50/50 dark:bg-zinc-900', 'badge' => 'bg-violet-600', 'line' => 'bg-violet-300 dark:bg-violet-800',
                'ikon' => 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6',
                'steps' => [
                    ['Kelola Wilayah & Sistem', 'Master kecamatan, desa, pengguna, pengaturan, backup database'],
                    ['Rekap Kabupaten', 'Laporan lintas kecamatan: LPP UED, Laporan Akhir USP, kolektibilitas'],
                    ['Jaga Integritas Data', 'Verifikasi integritas akuntansi otomatis tiap malam + audit log'],
                    ['Evaluasi & Kebijakan', 'Dasar pembinaan program USP/BUM Desa tingkat kabupaten'],
                ],
            ],
        ];
    @endphp

    <div class="grid md:grid-cols-3 gap-6">
        @foreach($lanes as $lane)
            <div class="rounded-xl border-2 {{ $lane['border'] }} overflow-hidden">
                <div class="{{ $lane['header'] }} text-white px-4 py-3 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $lane['ikon'] }}"/>
                    </svg>
                    <span class="font-semibold">{{ $lane['role'] }}</span>
                </div>
                <div class="p-4 {{ $lane['body'] }}">
                    @foreach($lane['steps'] as $i => $step)
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="w-7 h-7 shrink-0 rounded-full {{ $lane['badge'] }} text-white text-xs font-bold flex items-center justify-center">
                                    {{ $i + 1 }}
                                </div>
                                @if($i < count($lane['steps']) - 1)
                                    <div class="w-0.5 flex-1 my-1 {{ $lane['line'] }}"></div>
                                @endif
                            </div>
                            <div class="pb-4">
                                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $step[0] }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $step[1] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Alur data lintas level --}}
    <div class="mt-6 p-4 rounded-xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700">
        <p class="text-sm font-semibold mb-3 text-zinc-700 dark:text-zinc-200">Perjalanan satu transaksi:</p>
        <div class="flex flex-wrap items-center gap-2 text-xs">
            @foreach(['Transaksi Kas / Memorial / Angsuran', 'Jurnal Otomatis (Debit = Kredit)', 'Buku Besar (Neraca Saldo)', 'Laporan Keuangan (Neraca, Laba Rugi, Arus Kas, CALK)', 'Review Kecamatan', 'Rekap & Pembinaan Kabupaten'] as $i => $node)
                @if($i > 0)
                    <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/>
                    </svg>
                @endif
                <span class="px-3 py-1.5 rounded-full font-medium
                    {{ $i < 4 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : ($i == 4 ? 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200' : 'bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-200') }}">
                    {{ $node }}
                </span>
            @endforeach
        </div>
        <p class="mt-3 text-xs text-zinc-500">
            Setiap periode bulanan dikunci setelah tutup buku; integritas debit=kredit diverifikasi otomatis setiap malam;
            seluruh perubahan tercatat di audit log.
        </p>
    </div>
</div>
