<x-layouts.app :title="__('Dashboard')">
    @php
        $user = auth()->user();
        
        // Pengumuman aktif
        $pengumumanAktif = \App\Models\Pengumuman::aktif()
            ->orderBy('prioritas', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();
    @endphp

    @if(auth()->user()->hasKabupatenScope())
        {{-- Dashboard tingkat kabupaten: Super Admin & Admin Kabupaten (Dinas PMD) --}}
        <div class="flex h-full w-full flex-1 flex-col gap-6">
            <div>
                <flux:heading size="xl">{{ auth()->user()->isSuperAdmin() ? 'Dashboard Super Admin PMD' : 'Dashboard Admin Kabupaten (Dinas PMD)' }}</flux:heading>
                <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Sistem Informasi Pelaporan Keuangan USP Desa
                </flux:heading>
            </div>

            {{-- KPI Keuangan Eksekutif --}}
            @include('partials.dashboard-kpi', ['scopeDesaId' => null, 'scopeKecamatanId' => null])

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <!-- Total Kecamatan -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium opacity-90">Total Kecamatan</h3>
                        <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold">{{ \App\Models\Kecamatan::where('status', 'aktif')->count() }}</p>
                    <p class="text-xs opacity-80 mt-1">Kecamatan aktif di sistem</p>
                </div>

                <!-- Total Desa -->
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium opacity-90">Total Desa</h3>
                        <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold">{{ \App\Models\Desa::where('status', 'aktif')->count() }}</p>
                    <p class="text-xs opacity-80 mt-1">Desa aktif di sistem</p>
                </div>

                <!-- Total Kelompok -->
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium opacity-90">Total Kelompok</h3>
                        <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold">{{ \App\Models\Kelompok::count() }}</p>
                    <p class="text-xs opacity-80 mt-1">Kelompok terdaftar</p>
                </div>

                <!-- Total Anggota -->
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium opacity-90">Total Anggota</h3>
                        <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold">{{ \App\Models\Anggota::count() }}</p>
                    <p class="text-xs opacity-80 mt-1">Anggota terdaftar</p>
                </div>
            </div>

            @php
                // Data untuk Super Admin
                $totalPinjamanDisalurkan = \App\Models\Pinjaman::sum('jumlah_pinjaman');
                $pinjamanAktif = \App\Models\Pinjaman::where('status_pinjaman', 'aktif')->count();
                $anggotaTerbaru = \App\Models\Anggota::with(['desa.kecamatan', 'kelompok'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                $pinjamanTerbaru = \App\Models\Pinjaman::with(['anggota', 'desa'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                $angsuranTerbaru = \App\Models\AngsuranPinjaman::with(['pinjaman.anggota'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                // Statistik peminjaman (data statistik + per jenis usaha)
                $pinjamanQuerySA = \App\Models\Pinjaman::query();
                $dashboardPinjaman = app(\App\Services\DashboardPinjamanService::class);
                $statsSA = $dashboardPinjaman->getStatistik($pinjamanQuerySA);
                $sektorListSA = $dashboardPinjaman->getStatistikPerSektor($pinjamanQuerySA);
                $sektorTotalSA = $dashboardPinjaman->getTotalUntukSektor($pinjamanQuerySA);
            @endphp

            <!-- Pengumuman -->
            @if($pengumumanAktif->count() > 0)
                <div class="space-y-3">
                    <h3 class="text-lg font-semibold">Pengumuman</h3>
                    @foreach($pengumumanAktif as $pengumuman)
                        <div class="bg-white dark:bg-zinc-900 rounded-lg border @if($pengumuman->tipe === 'penting') border-red-300 @elseif($pengumuman->tipe === 'peringatan') border-yellow-300 @else border-blue-300 @endif p-4 shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0">
                                    @if($pengumuman->tipe === 'penting')
                                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    @elseif($pengumuman->tipe === 'peringatan')
                                        <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @else
                                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-sm mb-1">{{ $pengumuman->judul }}</h4>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $pengumuman->isi }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-2">
                                        {{ $pengumuman->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Data Statistik & Peminjaman per Jenis Usaha --}}
            <div class="space-y-6">
                @include('partials.dashboard-statistik-pinjaman', [
                    'stats' => $statsSA,
                    'sektorList' => $sektorListSA,
                    'sektorTotal' => $sektorTotalSA,
                ])
            </div>

            <!-- Data Pinjaman -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Total Pinjaman Disalurkan -->
                <flux:card>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Pinjaman Disalurkan
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-950/20 rounded-lg">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">Total Disalurkan</span>
                                <span class="text-lg font-bold text-blue-600">Rp {{ number_format($totalPinjamanDisalurkan, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-950/20 rounded-lg">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">Pinjaman Aktif</span>
                                <span class="text-lg font-bold text-green-600">{{ $pinjamanAktif }} Pinjaman</span>
                            </div>
                        </div>
                    </div>
                </flux:card>

                <!-- Anggota Terbaru -->
                <flux:card>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                            Anggota Terbaru
                        </h3>
                        <div class="space-y-2">
                            @forelse($anggotaTerbaru as $anggota)
                                <div class="flex items-center justify-between p-2 hover:bg-zinc-50 dark:hover:bg-zinc-900 rounded">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">{{ $anggota->nama }}</p>
                                        <p class="text-xs text-zinc-500">{{ $anggota->desa->nama_desa ?? '-' }} • {{ $anggota->kelompok->nama_kelompok ?? '-' }}</p>
                                    </div>
                                    <span class="text-xs text-zinc-400">{{ $anggota->created_at->diffForHumans() }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500 text-center py-4">Belum ada anggota terbaru</p>
                            @endforelse
                        </div>
                    </div>
                </flux:card>
            </div>

            <!-- Aktivitas Terbaru -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Pinjaman Terbaru -->
                <flux:card>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Pinjaman Terbaru
                        </h3>
                        <div class="space-y-2">
                            @forelse($pinjamanTerbaru as $pinjaman)
                                <div class="flex items-center justify-between p-2 hover:bg-zinc-50 dark:hover:bg-zinc-900 rounded">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">{{ $pinjaman->anggota->nama ?? '-' }}</p>
                                        <p class="text-xs text-zinc-500">{{ $pinjaman->nomor_pinjaman }} • Rp {{ number_format($pinjaman->jumlah_pinjaman, 0, ',', '.') }}</p>
                                    </div>
                                    <span class="text-xs text-zinc-400">{{ $pinjaman->created_at->diffForHumans() }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500 text-center py-4">Belum ada pinjaman terbaru</p>
                            @endforelse
                        </div>
                    </div>
                </flux:card>

                <!-- Angsuran Terbaru -->
                <flux:card>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Pembayaran Terbaru
                        </h3>
                        <div class="space-y-2">
                            @forelse($angsuranTerbaru as $angsuran)
                                <div class="flex items-center justify-between p-2 hover:bg-zinc-50 dark:hover:bg-zinc-900 rounded">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">{{ $angsuran->pinjaman->anggota->nama ?? '-' }}</p>
                                        <p class="text-xs text-zinc-500">Angsuran ke-{{ $angsuran->angsuran_ke }} • Rp {{ number_format($angsuran->total_dibayar, 0, ',', '.') }}</p>
                                    </div>
                                    <span class="text-xs text-zinc-400">{{ $angsuran->created_at->diffForHumans() }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500 text-center py-4">Belum ada pembayaran terbaru</p>
                            @endforelse
                        </div>
                    </div>
                </flux:card>
            </div>
        </div>
    @elseif(auth()->user()->isAdminKecamatan())
        {{-- Admin Kecamatan Dashboard --}}
        <div class="flex h-full w-full flex-1 flex-col gap-6">
            <div>
                <flux:heading size="xl">Dashboard Admin Kecamatan {{ auth()->user()->kecamatan->nama_kecamatan ?? 'Kecamatan' }}</flux:heading>
                <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Sistem Informasi Pelaporan Keuangan USP Desa
                </flux:heading>
            </div>

            {{-- KPI Keuangan Eksekutif --}}
            @include('partials.dashboard-kpi', ['scopeDesaId' => null, 'scopeKecamatanId' => auth()->user()->kecamatan_id])

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <!-- Total Desa -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium opacity-90">Total Desa</h3>
                        <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold">{{ \App\Models\Desa::where('kecamatan_id', auth()->user()->kecamatan_id)->where('status', 'aktif')->count() }}</p>
                    <p class="text-xs opacity-80 mt-1">Desa aktif di kecamatan</p>
                </div>

                <!-- Total Kelompok -->
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium opacity-90">Total Kelompok</h3>
                        <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold">{{ \App\Models\Kelompok::whereHas('desa', function($q) { $q->where('kecamatan_id', auth()->user()->kecamatan_id); })->count() }}</p>
                    <p class="text-xs opacity-80 mt-1">Kelompok terdaftar</p>
                </div>

                <!-- Total Anggota -->
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium opacity-90">Total Anggota</h3>
                        <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold">{{ \App\Models\Anggota::whereHas('desa', function($q) { $q->where('kecamatan_id', auth()->user()->kecamatan_id); })->count() }}</p>
                    <p class="text-xs opacity-80 mt-1">Anggota terdaftar</p>
                </div>

                <!-- Total Admin Desa -->
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium opacity-90">Total Admin Desa</h3>
                        <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold">{{ \App\Models\User::where('kecamatan_id', auth()->user()->kecamatan_id)->where('role', 'admin_desa')->count() }}</p>
                    <p class="text-xs opacity-80 mt-1">Admin desa aktif</p>
                </div>
            </div>

            @php
                // Data untuk Admin Kecamatan
                $kecamatanId = auth()->user()->kecamatan_id;
                $pinjamanQueryKec = \App\Models\Pinjaman::whereHas('desa', function($q) use ($kecamatanId) {
                    $q->where('kecamatan_id', $kecamatanId);
                });
                $totalPinjamanDisalurkan = (clone $pinjamanQueryKec)->sum('jumlah_pinjaman');
                $pinjamanAktif = (clone $pinjamanQueryKec)->where('status_pinjaman', 'aktif')->count();
                $anggotaTerbaru = \App\Models\Anggota::with(['desa', 'kelompok'])
                    ->whereHas('desa', function($q) use ($kecamatanId) {
                        $q->where('kecamatan_id', $kecamatanId);
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                $pinjamanTerbaru = \App\Models\Pinjaman::with(['anggota', 'desa'])
                    ->whereHas('desa', function($q) use ($kecamatanId) {
                        $q->where('kecamatan_id', $kecamatanId);
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                $angsuranTerbaru = \App\Models\AngsuranPinjaman::with(['pinjaman.anggota', 'pinjaman.desa'])
                    ->whereHas('pinjaman.desa', function($q) use ($kecamatanId) {
                        $q->where('kecamatan_id', $kecamatanId);
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                $dashboardPinjamanKec = app(\App\Services\DashboardPinjamanService::class);
                $statsKec = $dashboardPinjamanKec->getStatistik($pinjamanQueryKec);
                $sektorListKec = $dashboardPinjamanKec->getStatistikPerSektor($pinjamanQueryKec);
                $sektorTotalKec = $dashboardPinjamanKec->getTotalUntukSektor($pinjamanQueryKec);
            @endphp

            <!-- Pengumuman -->
            @if($pengumumanAktif->count() > 0)
                <div class="space-y-3">
                    <h3 class="text-lg font-semibold">Pengumuman</h3>
                    @foreach($pengumumanAktif as $pengumuman)
                        <div class="bg-white dark:bg-zinc-900 rounded-lg border @if($pengumuman->tipe === 'penting') border-red-300 @elseif($pengumuman->tipe === 'peringatan') border-yellow-300 @else border-blue-300 @endif p-4 shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0">
                                    @if($pengumuman->tipe === 'penting')
                                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    @elseif($pengumuman->tipe === 'peringatan')
                                        <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @else
                                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-sm mb-1">{{ $pengumuman->judul }}</h4>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $pengumuman->isi }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-2">
                                        {{ $pengumuman->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Data Statistik & Peminjaman per Jenis Usaha --}}
            <div class="space-y-6">
                @include('partials.dashboard-statistik-pinjaman', [
                    'stats' => $statsKec,
                    'sektorList' => $sektorListKec,
                    'sektorTotal' => $sektorTotalKec,
                ])
            </div>

            <!-- Data Pinjaman -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Total Pinjaman Disalurkan -->
                <flux:card>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Pinjaman Disalurkan
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-950/20 rounded-lg">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">Total Disalurkan</span>
                                <span class="text-lg font-bold text-blue-600">Rp {{ number_format($totalPinjamanDisalurkan, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-950/20 rounded-lg">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">Pinjaman Aktif</span>
                                <span class="text-lg font-bold text-green-600">{{ $pinjamanAktif }} Pinjaman</span>
                            </div>
                        </div>
                    </div>
                </flux:card>

                <!-- Anggota Terbaru -->
                <flux:card>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                            Anggota Terbaru
                        </h3>
                        <div class="space-y-2">
                            @forelse($anggotaTerbaru as $anggota)
                                <div class="flex items-center justify-between p-2 hover:bg-zinc-50 dark:hover:bg-zinc-900 rounded">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">{{ $anggota->nama }}</p>
                                        <p class="text-xs text-zinc-500">{{ $anggota->desa->nama_desa ?? '-' }} • {{ $anggota->kelompok->nama_kelompok ?? '-' }}</p>
                                    </div>
                                    <span class="text-xs text-zinc-400">{{ $anggota->created_at->diffForHumans() }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500 text-center py-4">Belum ada anggota terbaru</p>
                            @endforelse
                        </div>
                    </div>
                </flux:card>
            </div>

            <!-- Aktivitas Terbaru -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Pinjaman Terbaru -->
                <flux:card>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Pinjaman Terbaru
                        </h3>
                        <div class="space-y-2">
                            @forelse($pinjamanTerbaru as $pinjaman)
                                <div class="flex items-center justify-between p-2 hover:bg-zinc-50 dark:hover:bg-zinc-900 rounded">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">{{ $pinjaman->anggota->nama ?? '-' }}</p>
                                        <p class="text-xs text-zinc-500">{{ $pinjaman->nomor_pinjaman }} • Rp {{ number_format($pinjaman->jumlah_pinjaman, 0, ',', '.') }}</p>
                                    </div>
                                    <span class="text-xs text-zinc-400">{{ $pinjaman->created_at->diffForHumans() }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500 text-center py-4">Belum ada pinjaman terbaru</p>
                            @endforelse
                        </div>
                    </div>
                </flux:card>

                <!-- Angsuran Terbaru -->
                <flux:card>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Pembayaran Terbaru
                        </h3>
                        <div class="space-y-2">
                            @forelse($angsuranTerbaru as $angsuran)
                                <div class="flex items-center justify-between p-2 hover:bg-zinc-50 dark:hover:bg-zinc-900 rounded">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">{{ $angsuran->pinjaman->anggota->nama ?? '-' }}</p>
                                        <p class="text-xs text-zinc-500">Angsuran ke-{{ $angsuran->angsuran_ke }} • Rp {{ number_format($angsuran->total_dibayar, 0, ',', '.') }}</p>
                                    </div>
                                    <span class="text-xs text-zinc-400">{{ $angsuran->created_at->diffForHumans() }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500 text-center py-4">Belum ada pembayaran terbaru</p>
                            @endforelse
                        </div>
                    </div>
                </flux:card>
            </div>
        </div>
    @else
        {{-- Admin Desa / Executive View Dashboard --}}
        <div class="flex h-full w-full flex-1 flex-col gap-6">
            <div>
                <flux:heading size="xl">Dashboard {{ auth()->user()->desa->nama_desa ?? 'Desa' }}</flux:heading>
                <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
                    @if(auth()->user()->isExecutiveView())
                        Mode Baca Saja (Executive View)
                    @else
                        Sistem Informasi Pelaporan Keuangan USP Desa
                    @endif
                </flux:heading>
            </div>

            {{-- KPI Keuangan Eksekutif --}}
            @include('partials.dashboard-kpi', ['scopeDesaId' => auth()->user()->desa_id, 'scopeKecamatanId' => null])

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <!-- Total Kelompok -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium opacity-90">Total Kelompok</h3>
                        <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold">{{ \App\Models\Kelompok::where('desa_id', auth()->user()->desa_id)->count() }}</p>
                    <p class="text-xs opacity-80 mt-1">Kelompok terdaftar</p>
                </div>

                <!-- Total Anggota -->
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium opacity-90">Total Anggota</h3>
                        <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold">{{ \App\Models\Anggota::where('desa_id', auth()->user()->desa_id)->count() }}</p>
                    <p class="text-xs opacity-80 mt-1">Anggota terdaftar</p>
                </div>

                <!-- Kelompok Aktif -->
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium opacity-90">Kelompok Aktif</h3>
                        <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold">{{ \App\Models\Kelompok::where('desa_id', auth()->user()->desa_id)->where('status', 'aktif')->count() }}</p>
                    <p class="text-xs opacity-80 mt-1">Kelompok dengan status aktif</p>
                </div>

                <!-- Anggota Aktif -->
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium opacity-90">Anggota Aktif</h3>
                        <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold">{{ \App\Models\Anggota::where('desa_id', auth()->user()->desa_id)->where('status', 'aktif')->count() }}</p>
                    <p class="text-xs opacity-80 mt-1">Anggota dengan status aktif</p>
                </div>
            </div>

            @php
                // Data untuk Admin Desa
                $desaId = auth()->user()->desa_id;
                $pinjamanQueryDesa = \App\Models\Pinjaman::where('desa_id', $desaId);
                $totalPinjamanDisalurkan = (clone $pinjamanQueryDesa)->sum('jumlah_pinjaman');
                $pinjamanAktif = (clone $pinjamanQueryDesa)->where('status_pinjaman', 'aktif')->count();
                $anggotaTerbaru = \App\Models\Anggota::with(['kelompok'])
                    ->where('desa_id', $desaId)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                $pinjamanTerbaru = \App\Models\Pinjaman::with(['anggota'])
                    ->where('desa_id', $desaId)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                $angsuranTerbaru = \App\Models\AngsuranPinjaman::with(['pinjaman.anggota'])
                    ->whereHas('pinjaman', function($q) use ($desaId) {
                        $q->where('desa_id', $desaId);
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                $dashboardPinjamanDesa = app(\App\Services\DashboardPinjamanService::class);
                $statsDesa = $dashboardPinjamanDesa->getStatistik($pinjamanQueryDesa);
                $sektorListDesa = $dashboardPinjamanDesa->getStatistikPerSektor($pinjamanQueryDesa);
                $sektorTotalDesa = $dashboardPinjamanDesa->getTotalUntukSektor($pinjamanQueryDesa);
            @endphp

            <!-- Pengumuman -->
            @if($pengumumanAktif->count() > 0)
                <div class="space-y-3">
                    <h3 class="text-lg font-semibold">Pengumuman</h3>
                    @foreach($pengumumanAktif as $pengumuman)
                        <div class="bg-white dark:bg-zinc-900 rounded-lg border @if($pengumuman->tipe === 'penting') border-red-300 @elseif($pengumuman->tipe === 'peringatan') border-yellow-300 @else border-blue-300 @endif p-4 shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0">
                                    @if($pengumuman->tipe === 'penting')
                                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    @elseif($pengumuman->tipe === 'peringatan')
                                        <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @else
                                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-sm mb-1">{{ $pengumuman->judul }}</h4>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $pengumuman->isi }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-2">
                                        {{ $pengumuman->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Data Statistik & Peminjaman per Jenis Usaha --}}
            <div class="space-y-6">
                @include('partials.dashboard-statistik-pinjaman', [
                    'stats' => $statsDesa,
                    'sektorList' => $sektorListDesa,
                    'sektorTotal' => $sektorTotalDesa,
                ])
            </div>

            <!-- Data Pinjaman -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Total Pinjaman Disalurkan -->
                <flux:card>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Pinjaman Disalurkan
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-950/20 rounded-lg">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">Total Disalurkan</span>
                                <span class="text-lg font-bold text-blue-600">Rp {{ number_format($totalPinjamanDisalurkan, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-950/20 rounded-lg">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">Pinjaman Aktif</span>
                                <span class="text-lg font-bold text-green-600">{{ $pinjamanAktif }} Pinjaman</span>
                            </div>
                        </div>
                    </div>
                </flux:card>

                <!-- Anggota Terbaru -->
                <flux:card>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                            Anggota Terbaru
                        </h3>
                        <div class="space-y-2">
                            @forelse($anggotaTerbaru as $anggota)
                                <div class="flex items-center justify-between p-2 hover:bg-zinc-50 dark:hover:bg-zinc-900 rounded">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">{{ $anggota->nama }}</p>
                                        <p class="text-xs text-zinc-500">{{ $anggota->kelompok->nama_kelompok ?? '-' }}</p>
                                    </div>
                                    <span class="text-xs text-zinc-400">{{ $anggota->created_at->diffForHumans() }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500 text-center py-4">Belum ada anggota terbaru</p>
                            @endforelse
                        </div>
                    </div>
                </flux:card>
            </div>

            <!-- Aktivitas Terbaru -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Pinjaman Terbaru -->
                <flux:card>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Pinjaman Terbaru
                        </h3>
                        <div class="space-y-2">
                            @forelse($pinjamanTerbaru as $pinjaman)
                                <div class="flex items-center justify-between p-2 hover:bg-zinc-50 dark:hover:bg-zinc-900 rounded">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">{{ $pinjaman->anggota->nama ?? '-' }}</p>
                                        <p class="text-xs text-zinc-500">{{ $pinjaman->nomor_pinjaman }} • Rp {{ number_format($pinjaman->jumlah_pinjaman, 0, ',', '.') }}</p>
                                    </div>
                                    <span class="text-xs text-zinc-400">{{ $pinjaman->created_at->diffForHumans() }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500 text-center py-4">Belum ada pinjaman terbaru</p>
                            @endforelse
                        </div>
                    </div>
                </flux:card>

                <!-- Angsuran Terbaru -->
                <flux:card>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Pembayaran Terbaru
                        </h3>
                        <div class="space-y-2">
                            @forelse($angsuranTerbaru as $angsuran)
                                <div class="flex items-center justify-between p-2 hover:bg-zinc-50 dark:hover:bg-zinc-900 rounded">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">{{ $angsuran->pinjaman->anggota->nama ?? '-' }}</p>
                                        <p class="text-xs text-zinc-500">Angsuran ke-{{ $angsuran->angsuran_ke }} • Rp {{ number_format($angsuran->total_dibayar, 0, ',', '.') }}</p>
                                    </div>
                                    <span class="text-xs text-zinc-400">{{ $angsuran->created_at->diffForHumans() }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500 text-center py-4">Belum ada pembayaran terbaru</p>
                            @endforelse
                        </div>
                    </div>
                </flux:card>
            </div>
        </div>
    @endif
</x-layouts.app>
