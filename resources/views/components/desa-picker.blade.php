{{-- Searchable dropdown pemilih desa untuk komponen Livewire.
     Pemakaian: <x-desa-picker :desas="$desas" />  (entangle ke $selectedDesaId) --}}
@props(['desas', 'label' => 'Desa'])

<div x-data="{
    search: '',
    open: false,
    selectedId: @entangle('selectedDesaId').live,
    desas: {{ $desas->map(fn ($d) => ['id' => $d->id, 'nama_desa' => $d->nama_desa])->toJson() }},
    get filteredDesas() {
        if (!this.search) return this.desas;
        return this.desas.filter(d => d.nama_desa.toLowerCase().includes(this.search.toLowerCase()));
    },
    get selectedName() {
        const d = this.desas.find(d => d.id == this.selectedId);
        return d ? d.nama_desa : '';
    },
    pilih(id) {
        this.selectedId = id;
        this.open = false;
        this.search = '';
    }
}" class="relative">
    <label class="block text-sm font-medium mb-1 text-zinc-700 dark:text-zinc-200">{{ $label }}</label>
    <button type="button" @click="open = !open; $nextTick(() => $refs.cari?.focus())"
        class="w-full flex items-center justify-between rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-left">
        <span x-text="selectedName || 'Pilih desa...'" :class="selectedName ? '' : 'text-zinc-400'"></span>
        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
        </svg>
    </button>

    <div x-show="open" @click.away="open = false" x-cloak
        class="absolute z-20 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg shadow-lg">
        <div class="p-2 border-b border-zinc-200 dark:border-zinc-700">
            <input type="text" x-ref="cari" x-model="search" placeholder="Ketik untuk mencari desa..."
                class="w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-2 py-1.5 text-sm" />
        </div>
        <div class="max-h-60 overflow-y-auto">
            <template x-for="desa in filteredDesas" :key="desa.id">
                <div @click="pilih(desa.id)"
                    class="px-3 py-2 text-sm cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700"
                    :class="desa.id == selectedId ? 'bg-indigo-50 dark:bg-indigo-900/40 font-medium' : ''"
                    x-text="desa.nama_desa"></div>
            </template>
            <div x-show="filteredDesas.length === 0" class="px-3 py-2 text-sm text-zinc-500">Tidak ada hasil</div>
        </div>
    </div>
</div>
