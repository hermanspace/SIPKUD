<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl">Tutup Buku Tahunan</flux:heading>
        <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
            Menutup saldo pendapatan &amp; beban ke SHU Tahun Berjalan (jurnal penutup 31 Desember),
            lalu reklasifikasi ke SHU Tahun Lalu per 1 Januari tahun berikutnya
        </flux:heading>
    </div>

    @if (session()->has('message'))
        <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">{{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">{{ session('error') }}</div>
    @endif

    <flux:card class="p-6">
        <div class="mb-6 grid gap-4 md:grid-cols-3">
            <flux:select wire:model.live="tahun" label="Tahun Buku">
                @for($y = (int) now()->format('Y'); $y >= 2024; $y--)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </flux:select>
            <flux:select wire:model.live="unitUsahaId" label="Unit Usaha">
                <option value="">Semua Unit</option>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->nama_unit }}</option>
                @endforeach
            </flux:select>
        </div>

        <!-- Pratinjau -->
        <div class="grid md:grid-cols-3 gap-4 mb-6">
            <div class="p-4 border rounded-lg">
                <p class="text-sm text-zinc-500">Total Pendapatan {{ $tahun }}</p>
                <p class="text-lg font-semibold">Rp {{ number_format($labaRugi['pendapatan'], 2, ',', '.') }}</p>
            </div>
            <div class="p-4 border rounded-lg">
                <p class="text-sm text-zinc-500">Total Beban {{ $tahun }}</p>
                <p class="text-lg font-semibold">Rp {{ number_format($labaRugi['beban'], 2, ',', '.') }}</p>
            </div>
            <div class="p-4 border rounded-lg {{ $labaRugi['laba_bersih'] < 0 ? 'bg-red-50' : 'bg-green-50' }}">
                <p class="text-sm text-zinc-500">Laba (SHU) Bersih</p>
                <p class="text-lg font-bold">Rp {{ number_format($labaRugi['laba_bersih'], 2, ',', '.') }}</p>
            </div>
        </div>

        <!-- Alokasi SHU -->
        @if($labaRugi['laba_bersih'] > 0)
            <div class="mb-6">
                <flux:heading size="sm" class="mb-2">Simulasi Alokasi SHU (sesuai konfigurasi AD/ART)</flux:heading>
                <table class="min-w-full text-sm border rounded">
                    <thead class="bg-zinc-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Komponen</th>
                            <th class="px-4 py-2 text-right">Persen</th>
                            <th class="px-4 py-2 text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alokasi as $item)
                            <tr class="border-t">
                                <td class="px-4 py-1.5">{{ $item['nama'] }}</td>
                                <td class="px-4 py-1.5 text-right">{{ $item['persen'] }}%</td>
                                <td class="px-4 py-1.5 text-right">Rp {{ number_format($item['jumlah'], 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="mt-2 text-xs text-zinc-500">
                    Persentase alokasi dikonfigurasi di <code>config/accounting.php</code> (alokasi_shu).
                    Pembagian aktual dilakukan sesuai keputusan Musyawarah Desa.
                </p>
            </div>
        @endif

        @if($sudahDitutup)
            <div class="p-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg">
                Tahun buku {{ $tahun }} <strong>sudah ditutup</strong>. Jurnal penutup dapat dilihat di Buku Memorial (jenis: penutup).
            </div>
        @else
            <div class="p-4 border border-red-200 rounded-lg">
                <p class="text-sm text-red-700 mb-3">
                    <strong>Perhatian:</strong> tutup buku membuat jurnal penutup permanen per 31 Desember {{ $tahun }}.
                    Pastikan seluruh transaksi tahun {{ $tahun }} sudah final (tidak ada draft).
                </p>
                <div class="flex items-end gap-3">
                    <flux:input wire:model="confirmText" label='Ketik "TUTUP" untuk melanjutkan' placeholder="TUTUP" autocomplete="off" class="max-w-xs" />
                    <flux:button variant="danger" wire:click="tutupTahun" wire:loading.attr="disabled">
                        Tutup Tahun Buku {{ $tahun }}
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:card>
</div>
