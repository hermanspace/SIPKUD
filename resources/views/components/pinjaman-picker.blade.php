{{-- Searchable dropdown pemilih pinjaman aktif untuk komponen Livewire.
     Ketik nama anggota atau nomor pinjaman untuk memfilter.
     Pemakaian: <x-pinjaman-picker :pinjaman="$pinjaman" />  (entangle ke $pinjaman_id) --}}
@props(['pinjaman', 'label' => 'Pinjaman'])

<div x-data="{
    search: '',
    open: false,
    selectedId: @entangle('pinjaman_id').live,
    daftar: {{ $pinjaman->map(fn ($p) => [
        'id' => $p->id,
        'nomor' => $p->nomor_pinjaman,
        'nama' => $p->anggota->nama,
        'label' => $p->nomor_pinjaman.' - '.$p->anggota->nama.' (Sisa: Rp '.number_format($p->sisa_pinjaman, 0, ',', '.').')',
    ])->toJson() }},
    get tersaring() {
        if (!this.search) return this.daftar;
        const q = this.search.toLowerCase();
        return this.daftar.filter(p =>
            p.nama.toLowerCase().includes(q) || String(p.nomor).toLowerCase().includes(q)
        );
    },
    get labelTerpilih() {
        const p = this.daftar.find(p => p.id == this.selectedId);
        return p ? p.label : '';
    },
    pilih(id) {
        this.selectedId = id;
        this.open = false;
        this.search = '';
    }
}" class="relative">
    <label class="block text-sm font-medium mb-1 text-zinc-700 dark:text-zinc-200">{{ $label }} <span class="text-red-500">*</span></label>
    <button type="button" @click="open = !open; $nextTick(() => $refs.cari?.focus())"
        class="w-full flex items-center justify-between rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-left">
        <span x-text="labelTerpilih || 'Pilih pinjaman...'" :class="labelTerpilih ? '' : 'text-zinc-400'" class="truncate"></span>
        <svg class="w-4 h-4 shrink-0 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
        </svg>
    </button>
    <div x-show="open" x-cloak @click.away="open = false"
        class="absolute z-20 mt-1 w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-lg">
        <div class="p-2 border-b border-zinc-200 dark:border-zinc-700">
            <input type="text" x-ref="cari" x-model="search"
                placeholder="Ketik nama anggota atau nomor pinjaman..."
                class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-1.5 text-sm" />
        </div>
        <div class="max-h-60 overflow-y-auto">
            <template x-for="p in tersaring" :key="p.id">
                <button type="button" @click="pilih(p.id)"
                    class="w-full px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-700"
                    :class="p.id == selectedId ? 'bg-indigo-50 dark:bg-indigo-900/40 font-medium' : ''">
                    <span class="block truncate" x-text="p.label"></span>
                </button>
            </template>
            <div x-show="tersaring.length === 0" class="px-3 py-2 text-sm text-zinc-500">Tidak ada hasil</div>
        </div>
    </div>
</div>
