@php
    $p = $pinjaman;
    $totalKontrak = $p->jumlah_pinjaman * (1 + $p->jasa_persen * $p->jangka_waktu_bulan / 100);
    $jasaKontrak = $totalKontrak - $p->jumlah_pinjaman;
    $progres = $p->jumlah_pinjaman > 0
        ? min(100, round($p->total_pokok_dibayar / $p->jumlah_pinjaman * 100))
        : 0;
    $warnaKolek = match ($p->kolektibilitas) {
        'lancar' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
        'kurang_lancar' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        'diragukan' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-200',
        default => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
    };
    $labelKolek = \Illuminate\Support\Str::of($p->kolektibilitas)->replace('_', ' ')->title();
@endphp

<div class="flex h-full w-full flex-1 flex-col gap-6">
    {{-- Kepala kartu --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <flux:heading size="xl">{{ $p->anggota->nama }}</flux:heading>
                @if($p->anggota->nik_sementara)
                    <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">NIK sementara</span>
                @endif
            </div>
            <flux:heading size="sm" class="mt-1 text-zinc-600 dark:text-zinc-400">
                {{ $p->nomor_pinjaman }}
                @if($p->no_sppk) &middot; SPPK {{ $p->no_sppk }} @endif
                @if($p->sumber === 'import_excel') &middot; <span class="text-zinc-400">data impor Excel</span> @endif
                @if(auth()->user()->hasKabupatenScope() || auth()->user()->isAdminKecamatan())
                    &middot; {{ $p->desa->nama_desa ?? '' }}
                @endif
            </flux:heading>
        </div>
        <div class="flex gap-2">
            @can('admin_desa')
                @if($p->status_pinjaman === 'aktif')
                    <flux:button variant="primary" :href="route('angsuran.create', ['pinjaman' => $p->id])" wire:navigate>Bayar Angsuran</flux:button>
                @endif
                <flux:button variant="ghost" :href="route('pinjaman.edit', $p->id)" wire:navigate>Edit</flux:button>
            @endcan
            <flux:button variant="ghost" :href="route('pinjaman.index')" wire:navigate>&larr; Daftar</flux:button>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="p-4 rounded-lg bg-indigo-50 dark:bg-indigo-900/30">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Pokok + Jasa Kontrak</p>
            <p class="text-lg font-bold">Rp {{ number_format($totalKontrak, 0, ',', '.') }}</p>
            <p class="text-xs text-zinc-500">pokok {{ number_format($p->jumlah_pinjaman, 0, ',', '.') }} &middot; jasa {{ number_format($jasaKontrak, 0, ',', '.') }}</p>
        </div>
        <div class="p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/30">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Sudah Dibayar</p>
            <p class="text-lg font-bold">Rp {{ number_format($p->total_pokok_dibayar + $p->total_jasa_dibayar, 0, ',', '.') }}</p>
            <p class="text-xs text-zinc-500">{{ $p->angsuran->count() }} kali angsuran &middot; {{ $progres }}% pokok</p>
        </div>
        <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-900/30">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Sisa Pokok</p>
            <p class="text-lg font-bold">Rp {{ number_format($p->sisa_pinjaman, 0, ',', '.') }}</p>
            <p class="text-xs text-zinc-500">status: {{ ucfirst($p->status_pinjaman) }}</p>
        </div>
        <div class="p-4 rounded-lg {{ $warnaKolek }}">
            <p class="text-xs opacity-70">Kolektibilitas</p>
            <p class="text-lg font-bold">{{ $labelKolek }}</p>
            <p class="text-xs opacity-70">tunggakan {{ $p->tunggakan_bulan }} bulan</p>
        </div>
    </div>

    {{-- Info kontrak --}}
    <flux:card>
        <div class="grid gap-x-8 gap-y-2 sm:grid-cols-2 lg:grid-cols-3 text-sm">
            <div class="flex justify-between sm:block"><span class="text-zinc-500">Tanggal Pinjaman</span><p class="font-medium">{{ $p->tanggal_pinjaman?->format('d/m/Y') }}</p></div>
            <div class="flex justify-between sm:block"><span class="text-zinc-500">Jangka Waktu</span><p class="font-medium">{{ $p->jangka_waktu_bulan }} bulan (s/d {{ $p->tanggal_pinjaman?->copy()->addMonthsNoOverflow($p->jangka_waktu_bulan)->format('d/m/Y') }})</p></div>
            <div class="flex justify-between sm:block"><span class="text-zinc-500">Jasa</span><p class="font-medium">{{ rtrim(rtrim(number_format($p->jasa_persen, 2, ',', '.'), '0'), ',') }}% / bulan</p></div>
            <div class="flex justify-between sm:block"><span class="text-zinc-500">Angsuran per Bulan (perkiraan)</span><p class="font-medium">Rp {{ number_format($totalKontrak / max($p->jangka_waktu_bulan, 1), 0, ',', '.') }}</p></div>
            <div class="flex justify-between sm:block"><span class="text-zinc-500">Sektor Usaha</span><p class="font-medium">{{ $p->sektorUsaha?->nama ?? '-' }}</p></div>
            <div class="flex justify-between sm:block"><span class="text-zinc-500">NIK Anggota</span><p class="font-medium">{{ $p->anggota->nik_sementara ? '(belum dilengkapi)' : $p->anggota->nik }}</p></div>
        </div>
    </flux:card>

    {{-- Riwayat angsuran --}}
    <flux:card>
        <flux:heading size="lg" class="mb-3">Riwayat Angsuran</flux:heading>
        @if($p->angsuran->isEmpty())
            <p class="text-sm text-zinc-500 py-6 text-center">Belum ada angsuran tercatat.</p>
        @else
            @php $sisaBerjalan = (float) $p->jumlah_pinjaman; @endphp
            <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700 text-xs text-zinc-500 uppercase">
                        <th class="px-3 py-2 text-left">Ke</th>
                        <th class="px-3 py-2 text-left">Tanggal Bayar</th>
                        <th class="px-3 py-2 text-right">Pokok</th>
                        <th class="px-3 py-2 text-right">Jasa</th>
                        <th class="px-3 py-2 text-right">Denda</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-right">Sisa Pokok</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($p->angsuran as $a)
                        @php $sisaBerjalan -= (float) $a->pokok_dibayar; @endphp
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="px-3 py-2">{{ $a->angsuran_ke }}</td>
                            <td class="px-3 py-2">{{ $a->tanggal_bayar?->format('d/m/Y') }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($a->pokok_dibayar, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($a->jasa_dibayar, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($a->denda_dibayar, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right font-medium">{{ number_format($a->total_dibayar, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right text-zinc-500">{{ number_format(max($sisaBerjalan, 0), 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-semibold border-t-2 border-zinc-300 dark:border-zinc-600">
                        <td class="px-3 py-2" colspan="2">Jumlah</td>
                        <td class="px-3 py-2 text-right">{{ number_format($p->total_pokok_dibayar, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($p->total_jasa_dibayar, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($p->angsuran->sum('denda_dibayar'), 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($p->angsuran->sum('total_dibayar'), 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($p->sisa_pinjaman, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
            </div>
            @if($p->sumber === 'import_excel')
                <p class="mt-3 text-xs text-zinc-500">
                    Catatan: riwayat sebelum migrasi ke SIPKUD berasal dari total kumulatif Excel yang dibagi rata per bulan
                    (tanggal dan nominal per baris merupakan perkiraan; totalnya persis sesuai Excel).
                </p>
            @endif
        @endif
    </flux:card>
</div>
