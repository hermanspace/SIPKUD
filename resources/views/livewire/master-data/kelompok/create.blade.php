<div class="flex h-full w-full flex-1 flex-col gap-6">

    <div>
        <flux:heading size="xl">Tambah Kelompok</flux:heading>
        <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
            Tambah kelompok baru untuk mengkategorikan anggota
        </flux:heading>
    </div>

    <flux:card class="p-6">
        <form wire:submit="save" class="space-y-6">
            <flux:input 
                wire:model="nama_kelompok" 
                label="Nama Kelompok" 
                placeholder="Masukkan nama kelompok"
                required
                autofocus
            />
            <flux:error name="nama_kelompok" />

            <flux:textarea 
                wire:model="keterangan" 
                label="Keterangan" 
                placeholder="Masukkan keterangan (opsional)"
                rows="3"
            />
            <flux:error name="keterangan" />

            <flux:select wire:model="status" label="Status" required>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
            </flux:select>
            <flux:error name="status" />

            <div class="flex items-center gap-4">
                <flux:button type="submit" variant="primary">
                    Simpan
                </flux:button>
                <flux:button 
                    wire:navigate
                    href="{{ route('kelompok.index') }}"
                    variant="ghost"
                >
                    Batal
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>

