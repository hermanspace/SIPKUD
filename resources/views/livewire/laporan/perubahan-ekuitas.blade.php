<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex justify-between items-start">
        <div>
            <flux:heading size="xl">Laporan Perubahan Ekuitas</flux:heading>
            <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
                Modal awal, laba bersih berjalan, dan prive per periode
            </flux:heading>
        </div>
        @if($data)
            <flux:button wire:click="exportPdf" variant="primary" size="sm">Export PDF</flux:button>
        @endif
    </div>

    <flux:card class="p-6">
        <div class="mb-6 grid gap-4 md:grid-cols-4">
            @if($desas->count() > 1)
                <flux:select wire:model.live="selectedDesaId" label="Desa">
                    @foreach($desas as $desa)
                        <option value="{{ $desa->id }}">{{ $desa->nama_desa }}</option>
                    @endforeach
                </flux:select>
            @endif
            <flux:select wire:model.live="unitUsahaId" label="Unit Usaha">
                <option value="">Semua Unit</option>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->nama_unit }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="bulan" label="Bulan">
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
                    <tr>
                        <td class="px-4 py-2">Modal Awal</td>
                        <td class="px-4 py-2 text-right">{{ number_format($data['modal_awal'], 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2">Laba (Rugi) Bersih Berjalan</td>
                        <td class="px-4 py-2 text-right {{ $data['laba_bersih'] < 0 ? 'text-red-600' : '' }}">
                            {{ number_format($data['laba_bersih'], 2, ',', '.') }}
                        </td>
                    </tr>
                    @foreach($data['detail_prive'] as $prive)
                        <tr>
                            <td class="px-8 py-1.5">{{ $prive['nama_akun'] }}</td>
                            <td class="px-4 py-1.5 text-right">{{ number_format($prive['saldo'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td class="px-4 py-2">Prive / Pengambilan</td>
                        <td class="px-4 py-2 text-right">{{ number_format($data['prive'], 2, ',', '.') }}</td>
                    </tr>
                    <tr class="bg-zinc-100 dark:bg-zinc-800 font-bold border-t-2">
                        <td class="px-4 py-2">MODAL AKHIR</td>
                        <td class="px-4 py-2 text-right">{{ number_format($data['modal_akhir'], 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        @endif
    </flux:card>
</div>
