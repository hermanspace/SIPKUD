<div class="flex h-full w-full flex-1 flex-col gap-6">

    <div>
        <flux:heading size="xl">Tambah Akun</flux:heading>
        <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
            Tambah akun baru ke Chart of Accounts (COA)
        </flux:heading>
    </div>

    <flux:card class="p-6">
        <form wire:submit="save" class="space-y-6">
            <flux:input 
                wire:model="kode_akun" 
                label="Kode Akun" 
                placeholder="Contoh: 1.1.01.001"
                required
                autofocus
            />
            <flux:text class="mt-1 text-xs text-zinc-500">
                Kode akun harus unik untuk desa ini
            </flux:text>
            <flux:error name="kode_akun" />

            <flux:input 
                wire:model="nama_akun" 
                label="Nama Akun" 
                placeholder="Masukkan nama akun"
                required
            />
            <flux:error name="nama_akun" />

            <flux:select wire:model="tipe_akun" label="Tipe Akun" required>
                <option value="aset">Aset</option>
                <option value="kewajiban">Kewajiban</option>
                <option value="ekuitas">Ekuitas</option>
                <option value="pendapatan">Pendapatan</option>
                <option value="beban">Beban</option>
            </flux:select>
            <flux:error name="tipe_akun" />

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
                    href="{{ route('akun.index') }}"
                    variant="ghost"
                >
                    Batal
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>

