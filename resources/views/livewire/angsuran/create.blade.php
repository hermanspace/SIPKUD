<div class="flex h-full w-full flex-1 flex-col gap-6">

    <div>
        <flux:heading size="xl">Tambah Angsuran</flux:heading>
        <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
            Catat pembayaran angsuran pinjaman
        </flux:heading>
    </div>

    <flux:card class="p-6">
        <form wire:submit="save" class="space-y-6">
            <x-pinjaman-picker :pinjaman="$pinjaman" />
            <flux:error name="pinjaman_id" />

            <flux:input 
                wire:model="tanggal_bayar" 
                type="date"
                label="Tanggal Bayar" 
                required
            />
            <flux:error name="tanggal_bayar" />

            <flux:input 
                wire:model="angsuran_ke" 
                label="Angsuran Ke" 
                type="number"
                min="1"
                placeholder="Masukkan angsuran ke berapa"
                required
            />
            <flux:error name="angsuran_ke" />

            <flux:input 
                wire:model.live="pokok_dibayar" 
                label="Pokok Dibayar (Rp)" 
                type="number"
                step="0.01"
                min="0"
                placeholder="Masukkan jumlah pokok yang dibayar"
                required
            />
            <flux:error name="pokok_dibayar" />

            <flux:input 
                wire:model.live="jasa_dibayar" 
                label="Jasa Dibayar (Rp)" 
                type="number"
                step="0.01"
                min="0"
                placeholder="Masukkan jumlah jasa yang dibayar"
                required
            />
            <flux:error name="jasa_dibayar" />

            <flux:input 
                wire:model.live="denda_dibayar" 
                label="Denda Dibayar (Rp)" 
                type="number"
                step="0.01"
                min="0"
                placeholder="Masukkan jumlah denda yang dibayar"
                required
            />
            <flux:error name="denda_dibayar" />

            <flux:input 
                wire:model="total_dibayar" 
                label="Total Dibayar (Rp)" 
                type="number"
                step="0.01"
                disabled
                readonly
            />
            <flux:text class="text-xs text-zinc-500">
                Total dibayar dihitung otomatis dari pokok + jasa + denda
            </flux:text>

            <div class="flex items-center gap-4">
                <flux:button type="submit" variant="primary">
                    Simpan
                </flux:button>
                <flux:button 
                    wire:navigate
                    href="{{ route('angsuran.index') }}"
                    variant="ghost"
                >
                    Batal
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
