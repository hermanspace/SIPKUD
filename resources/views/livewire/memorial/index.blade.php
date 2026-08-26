<div class="flex h-full w-full flex-1 flex-col gap-6">

    <div>
        <flux:heading size="xl">Buku Memorial</flux:heading>
        <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
            Kelola jurnal memorial untuk transaksi non-kas (input utama akuntansi)
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
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari nomor atau uraian..."
                    class="w-full sm:w-64"
                />
                <flux:select wire:model.live="unitFilter" label="Unit Usaha" class="w-full sm:w-48">
                    <option value="">Semua Unit</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->nama_unit }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="statusFilter" label="Status" class="w-full sm:w-48">
                    <option value="">Semua Status</option>
                    <option value="draft">Draft</option>
                    <option value="posted">Posted</option>
                    <option value="void">Void</option>
                </flux:select>
            </div>
            @if(auth()->user()->isAdminDesa())
                <flux:button 
                    wire:navigate 
                    href="{{ route('memorial.create') }}" 
                    variant="primary"
                >
                    Tambah Jurnal
                </flux:button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-4 py-3 text-left text-sm font-semibold">No</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Tanggal</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">No. Jurnal</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Unit Usaha</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Uraian</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Status</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Total</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jurnal as $item)
                        <tr class="border-b border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                            <td class="px-4 py-3 text-sm">{{ $jurnal->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 text-sm">
                                {{ \Carbon\Carbon::parse($item->tanggal_jurnal)->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="font-mono">{{ $item->nomor_jurnal }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ $item->unitUsaha?->nama_unit ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="max-w-xs truncate">{{ $item->uraian }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($item->status === 'draft')
                                    <flux:badge variant="warning">Draft</flux:badge>
                                @elseif($item->status === 'posted')
                                    <flux:badge variant="success">Posted</flux:badge>
                                @else
                                    <flux:badge variant="danger">Void</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-sm">
                                Rp {{ number_format($item->details->sum('jumlah'), 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if(auth()->user()->isAdminDesa())
                                    <div class="flex items-center justify-end gap-2">
                                        @if($item->status === 'draft')
                                            <flux:button 
                                                wire:navigate
                                                :href="route('memorial.edit', $item->id)"
                                                variant="ghost"
                                                size="sm"
                                            >
                                                <flux:icon.pencil class="size-4" />
                                            </flux:button>
                                        @endif
                                        @if($item->status === 'posted')
                                            <flux:button 
                                                wire:click="void({{ $item->id }})"
                                                wire:confirm="Apakah Anda yakin ingin membatalkan (void) jurnal ini?"
                                                variant="ghost"
                                                size="sm"
                                                class="text-red-600 hover:text-red-700 dark:text-red-400"
                                            >
                                                <flux:icon.x-circle class="size-4" />
                                            </flux:button>
                                        @endif
                                        @if($item->status === 'draft')
                                            <flux:button 
                                                wire:click="delete({{ $item->id }})"
                                                wire:confirm="Apakah Anda yakin ingin menghapus jurnal ini?"
                                                variant="ghost"
                                                size="sm"
                                                class="text-red-600 hover:text-red-700 dark:text-red-400"
                                            >
                                                <flux:icon.trash class="size-4" />
                                            </flux:button>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-sm text-zinc-500 dark:text-zinc-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-zinc-600 dark:text-zinc-400">
                                Tidak ada data jurnal memorial ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($jurnal->hasPages())
            <div class="mt-4">
                {{ $jurnal->links() }}
            </div>
        @endif
    </flux:card>
</div>
