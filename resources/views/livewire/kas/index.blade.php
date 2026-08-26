<div class="flex h-full w-full flex-1 flex-col gap-6">

    <div>
        <flux:heading size="xl">Kas Harian</flux:heading>
        <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
            Kelola transaksi kas harian (input utama akuntansi)
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
        <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-col gap-4 sm:flex-row">
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
                <flux:select wire:model.live="unit_usaha_id" class="w-full sm:w-48">
                    <option value="">Semua Unit</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->nama_unit }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="jenis_transaksi" class="w-full sm:w-48">
                    <option value="">Semua Jenis</option>
                    <option value="masuk">Kas Masuk</option>
                    <option value="keluar">Kas Keluar</option>
                </flux:select>
                <flux:input 
                    wire:model.live="tanggal_dari" 
                    type="date"
                    label="Dari"
                    class="w-full sm:w-40"
                />
                <flux:input 
                    wire:model.live="tanggal_sampai" 
                    type="date"
                    label="Sampai"
                    class="w-full sm:w-40"
                />
            </div>
            @if(auth()->user()->isAdminDesa())
                <flux:button 
                    wire:navigate 
                    href="{{ route('kas.create') }}" 
                    variant="primary"
                >
                    Tambah Transaksi
                </flux:button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-4 py-3 text-left text-sm font-semibold">No</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Tanggal</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Unit Usaha</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Uraian</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Jenis</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Jumlah</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transaksi as $item)
                        <tr class="border-b border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                            <td class="px-4 py-3 text-sm">{{ $transaksi->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 text-sm">
                                {{ \Carbon\Carbon::parse($item->tanggal_transaksi)->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium">{{ $item->unitUsaha?->nama_unit ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="max-w-xs truncate">{{ $item->uraian }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <flux:badge :variant="$item->jenis_transaksi === 'masuk' ? 'success' : 'warning'">
                                    {{ ucfirst($item->jenis_transaksi) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-sm">
                                Rp {{ number_format($item->jumlah, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if(auth()->user()->isAdminDesa())
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button 
                                            wire:navigate
                                            :href="route('kas.edit', $item->id)"
                                            variant="ghost"
                                            size="sm"
                                        >
                                            <flux:icon.pencil class="size-4" />
                                        </flux:button>
                                        <flux:button 
                                            wire:click="delete({{ $item->id }})"
                                            wire:confirm="Apakah Anda yakin ingin menghapus transaksi ini?"
                                            variant="ghost"
                                            size="sm"
                                            class="text-red-600 hover:text-red-700 dark:text-red-400"
                                        >
                                            <flux:icon.trash class="size-4" />
                                        </flux:button>
                                    </div>
                                @else
                                    <span class="text-sm text-zinc-500 dark:text-zinc-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-600 dark:text-zinc-400">
                                Tidak ada data transaksi kas ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transaksi instanceof \Illuminate\Contracts\Pagination\Paginator && $transaksi->hasPages())
            <div class="mt-4">
                {{ $transaksi->links() }}
            </div>
        @endif
    </flux:card>
</div>
