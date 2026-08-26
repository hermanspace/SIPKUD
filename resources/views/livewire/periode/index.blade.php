<div class="flex h-full w-full flex-1 flex-col gap-6">

    <div>
        <flux:heading size="xl">Manajemen Periode Akuntansi</flux:heading>
        <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
            Kelola periode akuntansi bulanan, closing, dan neraca saldo
        </flux:heading>
    </div>

    @if(isset($error))
        <flux:card class="p-6 border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="font-semibold text-red-800 dark:text-red-200">Akses Ditolak</p>
                    <p class="text-red-700 dark:text-red-300">{{ $error }}</p>
                </div>
            </div>
        </flux:card>
    @endif

    <flux:card class="p-6">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row">
            @if($desas->count() > 1)
            <div class="w-full sm:w-64" x-data="{ 
                search: '', 
                selectedId: @entangle('selectedDesaId'),
                desas: {{ $desas->toJson() }},
                get filteredDesas() {
                    if (!this.search) return this.desas;
                    return this.desas.filter(d => 
                        d.nama_desa.toLowerCase().includes(this.search.toLowerCase())
                    );
                },
                selectDesa(id, name) {
                    this.selectedId = id;
                    this.search = name;
                    this.$refs.dropdown.style.display = 'none';
                },
                init() {
                    const selected = this.desas.find(d => d.id === this.selectedId);
                    if (selected) this.search = selected.nama_desa;
                }
            }">
                <label class="block text-sm font-medium mb-1">Desa</label>
                <div class="relative">
                    <input 
                        type="text" 
                        x-model="search"
                        @focus="$refs.dropdown.style.display = 'block'"
                        @click.away="$refs.dropdown.style.display = 'none'"
                        placeholder="Ketik untuk mencari desa..."
                        class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm"
                    />
                    <div x-ref="dropdown" style="display: none;" class="absolute z-10 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        <template x-for="desa in filteredDesas" :key="desa.id">
                            <div 
                                @click="selectDesa(desa.id, desa.nama_desa)"
                                class="px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 cursor-pointer text-sm"
                                x-text="desa.nama_desa"
                            ></div>
                        </template>
                        <div x-show="filteredDesas.length === 0" class="px-3 py-2 text-sm text-zinc-500">
                            Tidak ada hasil
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="w-full sm:w-48">
                <flux:select wire:model.live="selectedUnitUsahaId" label="Unit Usaha">
                    <option value="">Semua Unit</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->nama_unit }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div class="w-full sm:w-40">
                <flux:select wire:model.live="tahun" label="Tahun">
                    @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </flux:select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-4 py-3 text-left text-sm font-semibold">Periode</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold">Jumlah Akun</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Total Debit</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Total Kredit</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold">Status</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($periodes as $periode)
                        <tr class="border-b border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium">{{ $periode['bulan_nama'] }}</div>
                                <div class="text-xs text-zinc-500">{{ $periode['periode'] }}</div>
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                {{ $periode['jumlah_akun'] }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-sm">
                                Rp {{ number_format($periode['total_debit'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-sm">
                                Rp {{ number_format($periode['total_kredit'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                @if($periode['status'] === 'closed')
                                    <flux:badge variant="danger" size="sm">
                                        <flux:icon.lock-closed class="size-3 mr-1" />
                                        Closed
                                    </flux:badge>
                                @else
                                    <flux:badge variant="success" size="sm">
                                        <flux:icon.lock-open class="size-3 mr-1" />
                                        Open
                                    </flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($periode['has_data'])
                                        <flux:button 
                                            wire:navigate
                                            href="{{ route('periode.show', ['desa_id' => $selectedDesaId, 'periode' => $periode['periode']]) }}"
                                            variant="ghost"
                                            size="sm"
                                        >
                                            <flux:icon.eye class="size-4" />
                                        </flux:button>
                                    @endif

                                    @if(auth()->user()->isAdminDesa())
                                        @if($periode['status'] === 'open')
                                            <flux:button 
                                                wire:click="recalculate('{{ $periode['periode'] }}')"
                                                wire:confirm="Recalculate periode {{ $periode['bulan_nama'] }}?"
                                                variant="ghost"
                                                size="sm"
                                                title="Recalculate"
                                            >
                                                <flux:icon.arrow-path class="size-4" />
                                            </flux:button>
                                            <flux:button 
                                                wire:click="closePeriod('{{ $periode['periode'] }}')"
                                                wire:confirm="Tutup periode {{ $periode['bulan_nama'] }}? Setelah ditutup, transaksi di periode ini tidak dapat diubah."
                                                variant="ghost"
                                                size="sm"
                                                class="text-red-600 hover:text-red-700"
                                                title="Close Period"
                                            >
                                                <flux:icon.lock-closed class="size-4" />
                                            </flux:button>
                                        @else
                                            <flux:button 
                                                wire:click="reopenPeriod('{{ $periode['periode'] }}')"
                                                wire:confirm="Buka kembali periode {{ $periode['bulan_nama'] }}?"
                                                variant="ghost"
                                                size="sm"
                                                class="text-green-600 hover:text-green-700"
                                                title="Reopen Period"
                                            >
                                                <flux:icon.lock-open class="size-4" />
                                            </flux:button>
                                        @endif
                                    @else
                                        <span class="text-sm text-zinc-500 dark:text-zinc-400">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">Informasi Periode Akuntansi</h4>
            <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                <li>• <strong>Open</strong>: Periode masih dapat menerima transaksi baru</li>
                <li>• <strong>Closed</strong>: Periode sudah ditutup, tidak dapat diubah</li>
                <li>• <strong>Recalculate</strong>: Hitung ulang neraca saldo dari jurnal</li>
                <li>• <strong>Close Period</strong>: Tutup periode dan buat opening balance periode berikutnya</li>
            </ul>
        </div>
    </flux:card>
</div>
