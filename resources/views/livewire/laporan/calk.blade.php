<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex justify-between items-start">
        <div>
            <flux:heading size="xl">Catatan atas Laporan Keuangan (CALK)</flux:heading>
            <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
                Pengungkapan kebijakan akuntansi dan rincian pos laporan keuangan (SAK EMKM / Kepmendesa 136/2022)
            </flux:heading>
        </div>
        @if($data)
            <flux:button wire:click="exportPdf" variant="primary" size="sm">Export PDF</flux:button>
        @endif
    </div>

    <flux:card class="p-6">
        <div class="mb-6 grid gap-4 md:grid-cols-3">
            @if($desas->count() > 1)
                <flux:select wire:model.live="selectedDesaId" label="Desa">
                    @foreach($desas as $desa)
                        <option value="{{ $desa->id }}">{{ $desa->nama_desa }}</option>
                    @endforeach
                </flux:select>
            @endif
            <flux:select wire:model.live="tahun" label="Tahun Buku">
                @for($y = (int) now()->format('Y'); $y >= 2024; $y--)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </flux:select>
        </div>

        @if($error)
            <div class="p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg">{{ $error }}</div>
        @elseif($data)
            @include('partials.calk-body', $data)
        @endif
    </flux:card>
</div>
