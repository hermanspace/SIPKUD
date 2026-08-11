<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex justify-between items-start">
        <div>
            <flux:heading size="xl">Laporan Arus Kas</flux:heading>
            <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
                Metode langsung - aktivitas operasi, investasi, dan pendanaan ({{ $periodeLabel }})
            </flux:heading>
        </div>
        @if($data)
            <flux:button wire:click="exportPdf" variant="primary" size="sm">Export PDF</flux:button>
        @endif
    </div>

    <flux:card class="p-6">
        <div class="mb-6 grid gap-4 md:grid-cols-4">
            @if($desas->count() > 1)
                <x-desa-picker :desas="$desas" />
            @endif
            <flux:select wire:model.live="unitUsahaId" label="Unit Usaha">
                <option value="">Semua Unit</option>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->nama_unit }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="bulan" label="Bulan">
                <option value="">Satu Tahun Penuh</option>
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}">{{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}</option>
                @endfor
            </flux:select>
            <flux:select wire:model.live="tahun" label="Tahun">
                @for($y = (int) now()->format('Y') + 1; $y >= 2024; $y--)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </flux:select>
        </div>

        @if($error)
            <div class="p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg">{{ $error }}</div>
        @elseif($data)
            <table class="min-w-full text-sm">
                <tbody>
                    @foreach([
                        'operasi' => 'ARUS KAS DARI AKTIVITAS OPERASI',
                        'investasi' => 'ARUS KAS DARI AKTIVITAS INVESTASI',
                        'pendanaan' => 'ARUS KAS DARI AKTIVITAS PENDANAAN',
                    ] as $key => $judul)
                        <tr class="bg-zinc-100 dark:bg-zinc-800">
                            <td colspan="2" class="px-4 py-2 font-semibold">{{ $judul }}</td>
                        </tr>
                        @foreach($data['aktivitas'][$key]['masuk'] as $row)
                            <tr>
                                <td class="px-8 py-1.5">Penerimaan - {{ $row['nama_akun'] }}</td>
                                <td class="px-4 py-1.5 text-right">{{ number_format($row['jumlah'], 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        @foreach($data['aktivitas'][$key]['keluar'] as $row)
                            <tr>
                                <td class="px-8 py-1.5">Pengeluaran - {{ $row['nama_akun'] }}</td>
                                <td class="px-4 py-1.5 text-right">({{ number_format($row['jumlah'], 2, ',', '.') }})</td>
                            </tr>
                        @endforeach
                        <tr class="border-t font-medium">
                            <td class="px-4 py-2">Kas neto dari aktivitas {{ $key }}</td>
                            <td class="px-4 py-2 text-right {{ $data['aktivitas'][$key]['neto'] < 0 ? 'text-red-600' : '' }}">
                                {{ number_format($data['aktivitas'][$key]['neto'], 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="border-t-2 font-semibold">
                        <td class="px-4 py-2">KENAIKAN (PENURUNAN) KAS</td>
                        <td class="px-4 py-2 text-right">{{ number_format($data['kenaikan_kas'], 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2">Saldo kas awal periode</td>
                        <td class="px-4 py-2 text-right">{{ number_format($data['saldo_awal_kas'], 2, ',', '.') }}</td>
                    </tr>
                    <tr class="bg-zinc-100 dark:bg-zinc-800 font-bold">
                        <td class="px-4 py-2">SALDO KAS AKHIR PERIODE</td>
                        <td class="px-4 py-2 text-right">{{ number_format($data['saldo_akhir_kas'], 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        @endif
    </flux:card>
</div>
