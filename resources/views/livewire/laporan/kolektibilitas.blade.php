<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex justify-between items-start">
        <div>
            <flux:heading size="xl">Kolektibilitas Pinjaman</flux:heading>
            <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
                Kualitas portofolio pinjaman (lancar / kurang lancar / diragukan / macet) dan penyisihan piutang
            </flux:heading>
        </div>
        @can('admin_desa')
            <flux:button wire:click="buatPenyisihan" variant="primary" size="sm"
                wire:confirm="Buat jurnal penyesuaian penyisihan piutang berdasarkan kolektibilitas saat ini?">
                Buat Jurnal Penyisihan
            </flux:button>
        @endcan
    </div>

    @if (session()->has('message'))
        <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">{{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">{{ session('error') }}</div>
    @endif

    <flux:card class="p-6">
        @if($desas->count() > 1)
            <div class="mb-6 max-w-sm">
                <x-desa-picker :desas="$desas" />
            </div>
        @endif

        @if($error)
            <div class="p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg">{{ $error }}</div>
        @elseif($data)
            <!-- Ringkasan per kategori -->
            <div class="grid md:grid-cols-5 gap-4 mb-6">
                @foreach($data['kategori'] as $nama => $k)
                    <div class="p-4 border rounded-lg {{ $nama === 'macet' ? 'bg-red-50' : ($nama === 'lancar' ? 'bg-green-50' : 'bg-yellow-50') }}">
                        <p class="text-xs uppercase text-zinc-500">{{ str_replace('_', ' ', $nama) }}</p>
                        <p class="text-lg font-semibold">{{ $k['jumlah'] }} pinjaman</p>
                        <p class="text-sm">Sisa: Rp {{ number_format($k['sisa'], 0, ',', '.') }}</p>
                        <p class="text-xs text-zinc-500">Penyisihan {{ $k['rate'] }}%: Rp {{ number_format($k['penyisihan'], 0, ',', '.') }}</p>
                    </div>
                @endforeach
                <div class="p-4 border rounded-lg bg-zinc-50">
                    <p class="text-xs uppercase text-zinc-500">NPL (bermasalah)</p>
                    <p class="text-2xl font-bold {{ $data['npl_persen'] > 5 ? 'text-red-600' : 'text-green-700' }}">
                        {{ number_format($data['npl_persen'], 2, ',', '.') }}%
                    </p>
                    <p class="text-xs text-zinc-500">Target penyisihan: Rp {{ number_format($data['total_penyisihan'], 0, ',', '.') }}</p>
                </div>
            </div>

            <!-- Daftar pinjaman -->
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nomor</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Anggota</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Desa</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Sisa Pinjaman</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Tunggakan (bln)</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kolektibilitas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($data['pinjaman'] as $p)
                            <tr>
                                <td class="px-4 py-2 font-mono">{{ $p->nomor_pinjaman }}</td>
                                <td class="px-4 py-2">{{ $p->anggota->nama ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $p->desa->nama_desa ?? '-' }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($p->sisa_pinjaman, 0, ',', '.') }}</td>
                                <td class="px-4 py-2 text-right">{{ $p->tunggakan_bulan }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 rounded text-xs font-medium
                                        {{ match($p->kolektibilitas) {
                                            'lancar' => 'bg-green-100 text-green-800',
                                            'kurang_lancar' => 'bg-yellow-100 text-yellow-800',
                                            'diragukan' => 'bg-orange-100 text-orange-800',
                                            default => 'bg-red-100 text-red-800',
                                        } }}">
                                        {{ str_replace('_', ' ', $p->kolektibilitas) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-zinc-500">Tidak ada pinjaman aktif.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </flux:card>
</div>
