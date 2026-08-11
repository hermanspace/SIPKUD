{{-- Baris KPI keuangan eksekutif - dipakai semua role dashboard.
     Param: $scopeDesaId (int|null), $scopeKecamatanId (int|null). Null keduanya = seluruh kabupaten. --}}
@php
    $scopeDesaId = $scopeDesaId ?? null;
    $scopeKecamatanId = $scopeKecamatanId ?? null;

    $desaFilter = function ($query, $kolomDesa = 'desa_id') use ($scopeDesaId, $scopeKecamatanId) {
        if ($scopeDesaId) {
            $query->where($kolomDesa, $scopeDesaId);
        } elseif ($scopeKecamatanId) {
            $query->whereIn($kolomDesa, \App\Models\Desa::where('kecamatan_id', $scopeKecamatanId)->pluck('id'));
        }

        return $query;
    };

    // 1. Saldo kas berjalan (saldo awal + masuk - keluar)
    $saldoKas = (float) $desaFilter(\Illuminate\Support\Facades\DB::table('transaksi_kas'))
        ->selectRaw("COALESCE(SUM(CASE WHEN jenis_transaksi = 'keluar' THEN -jumlah ELSE jumlah END), 0) as saldo")
        ->value('saldo');

    // 2. Portofolio pinjaman + NPL (kolektibilitas)
    $kpiKolek = app(\App\Services\KolektibilitasService::class)->ringkasan($scopeDesaId, $scopeKecamatanId);

    // 3. Laba berjalan tahun ini (mutasi ledger pendapatan - beban, tanpa jurnal penutup)
    $labaYtd = (float) $desaFilter(
        \Illuminate\Support\Facades\DB::table('neraca_saldo')
            ->join('akun', 'akun.id', '=', 'neraca_saldo.akun_id')
            ->where('neraca_saldo.periode', 'like', now()->format('Y').'-%')
            ->whereIn('akun.tipe_akun', ['pendapatan', 'beban']),
        'neraca_saldo.desa_id'
    )
        ->selectRaw('COALESCE(SUM(neraca_saldo.mutasi_kredit - neraca_saldo.mutasi_debit), 0) as laba')
        ->value('laba');

    $kpiCards = [
        ['label' => 'Saldo Kas', 'nilai' => 'Rp '.number_format($saldoKas, 0, ',', '.'), 'sub' => 'posisi berjalan', 'warna' => 'from-emerald-500 to-emerald-600', 'ikon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z'],
        ['label' => 'Sisa Pinjaman Beredar', 'nilai' => 'Rp '.number_format($kpiKolek['total_sisa'], 0, ',', '.'), 'sub' => $kpiKolek['pinjaman']->count().' pinjaman aktif', 'warna' => 'from-sky-500 to-sky-600', 'ikon' => 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33'],
        ['label' => 'NPL (Pinjaman Bermasalah)', 'nilai' => number_format($kpiKolek['npl_persen'], 2, ',', '.').'%', 'sub' => 'target < 5%', 'warna' => $kpiKolek['npl_persen'] > 5 ? 'from-red-500 to-red-600' : 'from-teal-500 to-teal-600', 'ikon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
        ['label' => 'Laba Berjalan '.now()->format('Y'), 'nilai' => 'Rp '.number_format($labaYtd, 0, ',', '.'), 'sub' => 'pendapatan - beban (YTD)', 'warna' => $labaYtd < 0 ? 'from-orange-500 to-orange-600' : 'from-violet-500 to-violet-600', 'ikon' => 'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941'],
    ];
@endphp

<div>
    <div class="flex items-center justify-between mb-3">
        <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-300 uppercase tracking-wide">Ringkasan Keuangan</p>
        <a href="{{ route('laporan.kolektibilitas') }}" wire:navigate class="text-xs text-indigo-600 hover:underline">Lihat detail kolektibilitas &rarr;</a>
    </div>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        @foreach($kpiCards as $kpi)
            <div class="bg-gradient-to-br {{ $kpi['warna'] }} rounded-lg shadow-lg p-5 text-white">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-medium opacity-90">{{ $kpi['label'] }}</h3>
                    <svg class="w-7 h-7 opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $kpi['ikon'] }}"/>
                    </svg>
                </div>
                <p class="text-2xl font-bold leading-tight">{{ $kpi['nilai'] }}</p>
                <p class="text-xs opacity-80 mt-1">{{ $kpi['sub'] }}</p>
            </div>
        @endforeach
    </div>
</div>
