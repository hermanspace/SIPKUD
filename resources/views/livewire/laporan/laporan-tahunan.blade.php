<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex justify-between items-start">
        <div>
            <flux:heading size="xl">Laporan Tahunan BUM Desa</flux:heading>
            <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
                Paket lengkap PP 11/2021: Laba Rugi, Perubahan Ekuitas, Neraca, Arus Kas, dan CALK dalam satu PDF
            </flux:heading>
        </div>
        @if($data)
            <flux:button wire:click="exportPdf" variant="primary">Unduh Laporan Tahunan (PDF)</flux:button>
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
            <div class="grid md:grid-cols-4 gap-4 mb-6">
                <div class="p-4 border rounded-lg">
                    <p class="text-sm text-zinc-500">Pendapatan {{ $data['tahun'] }}</p>
                    <p class="text-lg font-semibold">Rp {{ number_format($data['labaRugi']['pendapatan'], 0, ',', '.') }}</p>
                </div>
                <div class="p-4 border rounded-lg">
                    <p class="text-sm text-zinc-500">Beban {{ $data['tahun'] }}</p>
                    <p class="text-lg font-semibold">Rp {{ number_format($data['labaRugi']['beban'], 0, ',', '.') }}</p>
                </div>
                <div class="p-4 border rounded-lg {{ $data['labaRugi']['laba_bersih'] < 0 ? 'bg-red-50' : 'bg-green-50' }}">
                    <p class="text-sm text-zinc-500">Laba (SHU) Bersih</p>
                    <p class="text-lg font-bold">Rp {{ number_format($data['labaRugi']['laba_bersih'], 0, ',', '.') }}</p>
                </div>
                <div class="p-4 border rounded-lg {{ $data['tahunDitutup'] ? 'bg-green-50' : 'bg-yellow-50' }}">
                    <p class="text-sm text-zinc-500">Status Tahun Buku</p>
                    <p class="text-lg font-semibold">{{ $data['tahunDitutup'] ? 'Sudah ditutup' : 'Belum ditutup' }}</p>
                </div>
            </div>

            <div class="p-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg text-sm">
                <p class="font-medium mb-1">Isi paket PDF:</p>
                <ol class="list-decimal ml-5 space-y-0.5">
                    <li>Laporan Laba Rugi tahun {{ $data['tahun'] }}</li>
                    <li>Laporan Perubahan Ekuitas</li>
                    <li>Laporan Posisi Keuangan (Neraca) per 31 Desember {{ $data['tahun'] }}</li>
                    <li>Laporan Arus Kas tahun {{ $data['tahun'] }}</li>
                    <li>Catatan atas Laporan Keuangan (CALK)</li>
                </ol>
                @unless($data['tahunDitutup'])
                    <p class="mt-2"><strong>Saran:</strong> lakukan <a href="{{ route('periode.tutup-tahun') }}" class="underline">Tutup Buku Tahunan</a> dulu agar SHU terklasifikasi benar di neraca.</p>
                @endunless
            </div>
        @endif
    </flux:card>
</div>
