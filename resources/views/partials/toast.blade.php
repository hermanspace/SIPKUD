{{-- Toast notifikasi global: menangkap dispatch('success'/'error') dari SEMUA
     komponen Livewire. Satu-satunya penampil notifikasi - jangan buat listener
     per-halaman lagi. --}}
<div x-data="{
        daftar: [],
        nomor: 0,
        tambah(detail) {
            const id = ++this.nomor;
            this.daftar.push({ id, tipe: detail.tipe, pesan: detail.pesan, tampil: true });
            setTimeout(() => this.tutup(id), detail.tipe === 'success' ? 4000 : 7000);
        },
        tutup(id) {
            const t = this.daftar.find(t => t.id === id);
            if (t) t.tampil = false;
            setTimeout(() => { this.daftar = this.daftar.filter(t => t.id !== id); }, 300);
        },
    }"
    @sipkud-toast.window="tambah($event.detail)"
    class="fixed top-4 right-4 z-[9999] flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-2 pointer-events-none"
    aria-live="polite">
    <template x-for="t in daftar" :key="t.id">
        <div x-show="t.tampil"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             :class="t.tipe === 'success' ? 'bg-emerald-600' : 'bg-red-600'"
             class="pointer-events-auto flex items-start gap-3 rounded-lg px-4 py-3 text-sm text-white shadow-lg">
            <svg x-show="t.tipe === 'success'" class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <svg x-show="t.tipe !== 'success'" class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <span x-text="t.pesan" class="flex-1"></span>
            <button type="button" @click="tutup(t.id)" class="shrink-0 opacity-70 hover:opacity-100" aria-label="Tutup">&#10005;</button>
        </div>
    </template>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        const teruskan = (tipe, payload) => {
            const data = Array.isArray(payload) ? payload[0] : payload;
            const pesan = (data && data.message)
                || (tipe === 'success' ? 'Berhasil.' : 'Terjadi kesalahan.');
            window.dispatchEvent(new CustomEvent('sipkud-toast', { detail: { tipe, pesan } }));
        };
        Livewire.on('success', (p) => teruskan('success', p));
        Livewire.on('error', (p) => teruskan('error', p));
    });
</script>
