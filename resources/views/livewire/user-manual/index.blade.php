<div class="space-y-6">
    @php
        $user = auth()->user();
        $role = $user->isSuperAdmin() ? 'super_admin' : ($user->isAdminKabupaten() ? 'admin_kabupaten' : ($user->isAdminKecamatan() ? 'admin_kecamatan' : ($user->isExecutiveView() ? 'executive_view' : 'admin_desa')));
        $roleLabel = [
            'super_admin' => 'Super Admin (Operator Sistem)',
            'admin_kabupaten' => 'Admin Kabupaten (Dinas PMD)',
            'admin_kecamatan' => 'Admin Kecamatan',
            'admin_desa' => 'Admin Desa',
            'executive_view' => 'Executive View (Baca Saja)',
        ][$role];

        // Langkah prioritas "Mulai Cepat" per role
        $mulaiCepat = [
            'admin_desa' => [
                ['Isi Master Data', 'Lengkapi Kelompok, Anggota, dan Unit Usaha desa Anda terlebih dahulu.', 'kelompok.index'],
                ['Set Saldo Awal Kas', 'Masukkan saldo kas awal dari pembukuan manual - jurnal dibuat otomatis.', 'kas.saldo-awal'],
                ['Catat Transaksi Harian', 'Kas Harian untuk uang masuk/keluar, Pinjaman & Angsuran untuk simpan pinjam.', 'kas.index'],
                ['Cek Laporan & Tutup Periode', 'Verifikasi Neraca Saldo tiap akhir bulan, lalu kunci periodenya.', 'periode.index'],
                ['Tutup Buku Akhir Tahun', 'Jalankan Tutup Buku Tahunan agar laba masuk ke SHU dan siapkan Laporan Tahunan.', 'periode.tutup-tahun'],
            ],
            'admin_kecamatan' => [
                ['Kelola Pengguna Desa', 'Buat & kelola akun Admin Desa di kecamatan Anda.', 'pengguna.index'],
                ['Pantau Kolektibilitas', 'Cek NPL tiap desa - deteksi dini pinjaman bermasalah.', 'laporan.kolektibilitas'],
                ['Review Laporan Desa', 'Bandingkan Neraca, Laba Rugi, dan LPP UED antar desa.', 'laporan.lpp-ued'],
                ['Kelola Master Akun (COA)', 'Bagan akun bersifat global - hanya Anda dan Super Admin yang bisa mengubah.', 'akun.index'],
            ],
            'super_admin' => [
                ['Siapkan Wilayah & Pengguna', 'Master Kecamatan, Desa, lalu akun Admin Kecamatan/Desa.', 'kecamatan.index'],
                ['Atur Identitas Sistem', 'Nama instansi, logo, dan pengaturan lain (dipakai di kop laporan).', 'pengaturan.index'],
                ['Pantau Dashboard Kabupaten', 'KPI: saldo kas, pinjaman beredar, NPL, laba berjalan seluruh desa.', 'dashboard'],
                ['Pastikan Backup Berjalan', 'Backup otomatis tiap malam - unduh berkala ke luar server.', 'backup.index'],
            ],
            'admin_kabupaten' => [
                ['Siapkan Wilayah & Pengguna', 'Master Kecamatan, Desa, lalu akun Admin Kecamatan/Desa/Executive.', 'kecamatan.index'],
                ['Pantau Dashboard Kabupaten', 'KPI: saldo kas, pinjaman beredar, NPL, laba berjalan seluruh desa.', 'dashboard'],
                ['Bina Desa Bermasalah', 'Cek Kolektibilitas se-kabupaten - dampingi desa dengan NPL di atas 5%.', 'laporan.kolektibilitas'],
                ['Umumkan Kebijakan', 'Pengumuman tampil di dashboard seluruh pengguna.', 'pengumuman.index'],
            ],
            'executive_view' => [
                ['Lihat Dashboard', 'Ringkasan Keuangan: saldo kas, pinjaman beredar, NPL, dan laba berjalan.', 'dashboard'],
                ['Buka Laporan Kunci', 'Neraca, Laba Rugi, Arus Kas, dan Kolektibilitas - semua baca saja.', 'laporan.neraca'],
                ['Unduh Laporan Tahunan', 'Paket lengkap 5 laporan PP 11/2021 dalam satu PDF.', 'laporan.tahunan'],
            ],
        ][$role];

        // Panduan modul (difilter sesuai role)
        $modul = [
            'Master Data' => [
                'ikon' => 'M4 6h16M4 10h16M4 14h16M4 18h16',
                'items' => [
                    ['Kelompok & Anggota', 'Daftarkan kelompok usaha lalu anggotanya (NIK wajib). Anggota adalah subjek pinjaman.', ['admin_desa', 'admin_kecamatan', 'admin_kabupaten', 'super_admin']],
                    ['Akun / COA (Global)', 'Bagan akun berlaku untuk seluruh desa. Hanya Admin Kecamatan & Super Admin yang boleh menambah/mengubah - Admin Desa memakai akun yang tersedia.', ['admin_kecamatan', 'admin_kabupaten', 'super_admin']],
                    ['Unit Usaha', 'Pisahkan pembukuan per unit (USP, UED-SP, dll). Semua transaksi & laporan bisa difilter per unit.', ['admin_desa', 'admin_kabupaten', 'super_admin']],
                    ['Sektor Usaha', 'Kategori usaha peminjam - dipakai statistik dashboard.', ['admin_desa', 'admin_kecamatan', 'admin_kabupaten', 'super_admin']],
                    ['Aset Tetap (BARU)', 'Daftarkan aset (harga, umur ekonomis, akun terkait). Penyusutan garis lurus dijurnal OTOMATIS tiap awal bulan; nilai buku tampil di daftar.', ['admin_desa', 'admin_kabupaten', 'super_admin']],
                    ['Impor Data Historis (BARU)', 'Muat riwayat pemanfaat + pinjaman dari file Excel UEK-SP lama (sheet LPP-UEK) sekali saat mulai memakai SIPKUD: anggota dibuat dengan NIK sementara, riwayat angsuran terisi, dan saldo awal piutang dijurnal otomatis. Aman diunggah ulang (anti-duplikat No SPPK).', ['admin_desa']],
                ],
            ],
            'Transaksi Harian' => [
                'ikon' => 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33',
                'items' => [
                    ['Saldo Awal Kas', 'Sekali di awal pemakaian: masukkan saldo kas dari pembukuan manual. Jurnal (Kas / Modal) dibuat otomatis.', ['admin_desa']],
                    ['Kas Harian', 'Semua uang masuk/keluar: pilih akun kas + akun lawan - sistem membuat jurnal double entry otomatis.', ['admin_desa']],
                    ['Buku Memorial', 'Transaksi non-kas (koreksi, penyesuaian manual): susun baris debit & kredit sendiri, wajib balance.', ['admin_desa']],
                    ['Pinjaman', 'Pencairan pinjaman anggota - otomatis membuat transaksi kas keluar + jurnal Piutang/Kas.', ['admin_desa']],
                    ['Angsuran', 'Pembayaran angsuran - otomatis memecah jurnal: pokok (Piutang), jasa & denda (Pendapatan).', ['admin_desa']],
                ],
            ],
            'Periode & Tutup Buku' => [
                'ikon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
                'items' => [
                    ['Tutup Periode Bulanan', 'Kunci bulan yang sudah final di menu Manajemen Periode - transaksi terkunci tidak bisa diubah/dihapus. Saldo akhir otomatis jadi saldo awal bulan berikutnya.', ['admin_desa', 'admin_kecamatan', 'admin_kabupaten', 'super_admin']],
                    ['Penyisihan Piutang (BARU)', 'Di laporan Kolektibilitas, klik "Buat Jurnal Penyisihan" - sistem menghitung cadangan kerugian piutang berdasar umur tunggakan (PPAP: lancar 0,5%, kurang lancar 10%, diragukan 50%, macet 100%).', ['admin_desa']],
                    ['Tutup Buku Tahunan (BARU)', 'Menu Periode > Tutup Tahun: seluruh pendapatan & beban ditutup ke SHU Tahun Berjalan (jurnal penutup 31 Des), lalu direklasifikasi ke SHU Tahun Lalu. Ada pratinjau laba & simulasi alokasi SHU sesuai AD/ART sebelum konfirmasi.', ['admin_desa']],
                ],
            ],
            'Laporan' => [
                'ikon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z',
                'items' => [
                    ['Neraca Saldo, Laba Rugi, Neraca', 'Laporan inti bulanan - semuanya otomatis dari buku besar (ledger), tidak bisa diedit manual.', ['admin_desa', 'admin_kecamatan', 'admin_kabupaten', 'super_admin', 'executive_view']],
                    ['Arus Kas (BARU)', 'Metode langsung: aktivitas operasi / investasi / pendanaan, per bulan atau setahun penuh.', ['admin_desa', 'admin_kecamatan', 'admin_kabupaten', 'super_admin', 'executive_view']],
                    ['Perubahan Ekuitas (BARU)', 'Modal awal + laba berjalan - prive = modal akhir.', ['admin_desa', 'admin_kecamatan', 'admin_kabupaten', 'super_admin', 'executive_view']],
                    ['Kolektibilitas & NPL (BARU)', 'Kualitas portofolio pinjaman per kategori umur tunggakan + rasio NPL. Alat pembinaan utama kecamatan/kabupaten.', ['admin_desa', 'admin_kecamatan', 'admin_kabupaten', 'super_admin', 'executive_view']],
                    ['CALK (BARU)', 'Catatan atas Laporan Keuangan: kebijakan akuntansi, rincian akun material, kualitas piutang - dibangkitkan otomatis.', ['admin_desa', 'admin_kecamatan', 'admin_kabupaten', 'super_admin', 'executive_view']],
                    ['Laporan Tahunan (BARU)', 'Satu PDF berisi 5 komponen wajib PP 11/2021 + kop & tanda tangan resmi - siap untuk Musyawarah Desa.', ['admin_desa', 'admin_kecamatan', 'admin_kabupaten', 'super_admin', 'executive_view']],
                    ['LPP UED, Buku Kas, Laporan Akhir USP', 'Laporan manajemen program UED-SP. Laporan Akhir USP kini menghitung SHU = laba bersih + tabel alokasi sesuai AD/ART.', ['admin_desa', 'admin_kecamatan', 'admin_kabupaten', 'super_admin', 'executive_view']],
                ],
            ],
            'Administrasi Sistem' => [
                'ikon' => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z',
                'items' => [
                    ['Pengguna', 'Super Admin membuat semua akun; Admin Kecamatan membuat Admin Desa di wilayahnya.', ['admin_kecamatan', 'admin_kabupaten', 'super_admin']],
                    ['Pengumuman', 'Pengumuman resmi tampil di dashboard semua pengguna.', ['admin_kabupaten', 'super_admin']],
                    ['Pengaturan Sistem', 'Nama instansi & logo (dipakai kop laporan PDF) - khusus Super Admin.', ['super_admin']],
                    ['Backup & Restore (BARU)', 'Menu Backup Database: buat/unduh/unggah/pulihkan backup penuh dari panel. Backup otomatis tiap malam 01:30; restore selalu membuat safety snapshot + verifikasi integritas otomatis.', ['super_admin']],
                    ['Penjaga Integritas Otomatis', 'Setiap malam sistem memverifikasi debit=kredit, konsistensi jurnal-ledger, dan saldo. Semua perubahan tercatat di audit log.', ['super_admin']],
                ],
            ],
        ];

        // Glosarium singkat
        $glosarium = [
            ['Debit / Kredit', 'Dua sisi pencatatan double entry - total keduanya selalu sama di tiap jurnal.'],
            ['Jurnal', 'Catatan resmi satu transaksi. Di SIPKUD hampir semua jurnal dibuat otomatis.'],
            ['Neraca Saldo (Ledger)', 'Rekap saldo semua akun per bulan - sumber seluruh laporan.'],
            ['Kolektibilitas', 'Kualitas pinjaman berdasar tunggakan: lancar, kurang lancar (1-3 bln), diragukan (4-6 bln), macet (>=7 bln).'],
            ['NPL', 'Non-Performing Loan - porsi pinjaman bermasalah. Sehat bila di bawah 5%.'],
            ['PPAP / Penyisihan', 'Cadangan kerugian piutang yang dibentuk sesuai kolektibilitas.'],
            ['SHU', 'Sisa Hasil Usaha = laba bersih (pendapatan - beban), dialokasikan sesuai AD/ART.'],
            ['Tutup Buku', 'Penguncian periode (bulanan) dan penutupan pendapatan/beban ke SHU (tahunan).'],
            ['CALK', 'Catatan atas Laporan Keuangan - pengungkapan kebijakan & rincian angka laporan.'],
        ];

        $adaRoute = fn ($nama) => \Illuminate\Support\Facades\Route::has($nama);
    @endphp

    {{-- Hero --}}
    <div class="bg-gradient-to-r from-indigo-600 to-violet-600 rounded-xl shadow-lg p-8 text-white">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold">Panduan Pengguna SIPKUD</h1>
                <p class="mt-2 text-indigo-100 max-w-2xl">
                    Sistem Informasi Pelaporan Keuangan USP Desa - pembukuan double entry otomatis,
                    laporan sesuai PP 11/2021 &amp; Kepmendesa 136/2022, dan pengawasan berjenjang
                    desa &rarr; kecamatan &rarr; kabupaten.
                </p>
            </div>
            <span class="px-4 py-2 rounded-full bg-white/20 text-sm font-semibold whitespace-nowrap">
                Anda login sebagai: {{ $roleLabel }}
            </span>
        </div>
    </div>

    {{-- Mulai Cepat (prioritas sesuai role) --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
        <flux:heading size="lg" class="mb-1">Mulai Cepat - Prioritas Anda</flux:heading>
        <p class="text-sm text-zinc-500 mb-5">Urutan langkah terpenting untuk peran {{ $roleLabel }}</p>
        <div class="grid md:grid-cols-{{ min(count($mulaiCepat), 5) }} gap-4">
            @foreach($mulaiCepat as $i => $langkah)
                <a @if($adaRoute($langkah[2])) href="{{ route($langkah[2]) }}" wire:navigate @endif
                    class="group block p-4 rounded-xl border-2 border-indigo-100 dark:border-indigo-900 hover:border-indigo-400 hover:shadow-md transition">
                    <div class="w-8 h-8 rounded-full bg-indigo-600 text-white text-sm font-bold flex items-center justify-center mb-3">{{ $i + 1 }}</div>
                    <p class="font-semibold text-sm text-zinc-800 dark:text-zinc-100 group-hover:text-indigo-600">{{ $langkah[0] }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ $langkah[1] }}</p>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Flowchart alur proses akuntansi (semua level akses) --}}
    @include('partials.alur-akuntansi')

    {{-- Panduan Modul (accordion, difilter sesuai role) --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6" x-data="{ buka: 'Master Data' }">
        <flux:heading size="lg" class="mb-1">Panduan Modul</flux:heading>
        <p class="text-sm text-zinc-500 mb-5">Hanya modul yang relevan dengan hak akses Anda yang ditampilkan. Tanda <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-xs font-semibold">BARU</span> = fitur pembaruan terakhir.</p>

        <div class="space-y-3">
            @foreach($modul as $namaKategori => $kategori)
                @php
                    $itemTampil = collect($kategori['items'])->filter(fn ($it) => in_array($role, $it[2]));
                @endphp
                @if($itemTampil->isNotEmpty())
                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden">
                        <button type="button" @click="buka = buka === '{{ $namaKategori }}' ? '' : '{{ $namaKategori }}'"
                            class="w-full flex items-center justify-between px-5 py-3.5 bg-zinc-50 dark:bg-zinc-900 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-left">
                            <span class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $kategori['ikon'] }}"/>
                                </svg>
                                <span class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $namaKategori }}</span>
                                <span class="text-xs text-zinc-400">({{ $itemTampil->count() }} topik)</span>
                            </span>
                            <svg class="w-5 h-5 text-zinc-400 transition-transform" :class="buka === '{{ $namaKategori }}' ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="buka === '{{ $namaKategori }}'" x-cloak class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach($itemTampil as $item)
                                <div class="px-5 py-3.5">
                                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                                        {!! str_replace('(BARU)', '<span class="ml-1 px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-xs font-semibold align-middle">BARU</span>', e($item[0])) !!}
                                    </p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">{{ $item[1] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Peran & Hak Akses --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
        <flux:heading size="lg" class="mb-4">Peran &amp; Hak Akses</flux:heading>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-900 text-left text-xs uppercase text-zinc-500">
                        <th class="px-4 py-2.5">Peran</th>
                        <th class="px-4 py-2.5">Cakupan Data</th>
                        <th class="px-4 py-2.5">Yang Bisa Dilakukan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    <tr><td class="px-4 py-2.5 font-medium">Super Admin (Operator)</td><td class="px-4 py-2.5">Seluruh kabupaten</td><td class="px-4 py-2.5">Semua kemampuan Admin Kabupaten + pengaturan sistem serta backup &amp; restore</td></tr>
                    <tr><td class="px-4 py-2.5 font-medium">Admin Kabupaten (Dinas PMD)</td><td class="px-4 py-2.5">Seluruh kabupaten</td><td class="px-4 py-2.5">Kelola wilayah, pengguna (selain Super Admin), COA, pengumuman, semua laporan - tanpa pengaturan sistem &amp; backup</td></tr>
                    <tr><td class="px-4 py-2.5 font-medium">Admin Kecamatan</td><td class="px-4 py-2.5">Desa-desa di kecamatannya</td><td class="px-4 py-2.5">Baca semua data desa, kelola pengguna desa &amp; COA, pembinaan via laporan/NPL</td></tr>
                    <tr><td class="px-4 py-2.5 font-medium">Admin Desa</td><td class="px-4 py-2.5">Desanya sendiri</td><td class="px-4 py-2.5">Input semua transaksi, tutup periode &amp; tutup tahun, kelola master data desa</td></tr>
                    <tr><td class="px-4 py-2.5 font-medium">Executive View</td><td class="px-4 py-2.5">Sesuai penugasan</td><td class="px-4 py-2.5">Baca saja: dashboard KPI &amp; seluruh laporan</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Glosarium --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
        <flux:heading size="lg" class="mb-4">Glosarium Singkat</flux:heading>
        <dl class="grid md:grid-cols-3 gap-x-8 gap-y-3">
            @foreach($glosarium as $g)
                <div>
                    <dt class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $g[0] }}</dt>
                    <dd class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $g[1] }}</dd>
                </div>
            @endforeach
        </dl>
        <p class="mt-5 text-xs text-zinc-400">
            Standar rujukan: PP No. 11 Tahun 2021, Kepmendesa PDTT No. 136 Tahun 2022, SAK EMKM, dan konvensi penilaian kesehatan KSP/USP.
        </p>
    </div>
</div>
