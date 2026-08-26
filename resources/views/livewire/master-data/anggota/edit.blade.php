<div class="flex h-full w-full flex-1 flex-col gap-6">

    <div>
        <flux:heading size="xl">Edit Anggota</flux:heading>
        <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
            Edit data anggota
        </flux:heading>
    </div>

    <flux:card class="p-6">
        <form wire:submit="update" class="space-y-6">
            <flux:input 
                wire:model="nama" 
                label="Nama Anggota" 
                placeholder="Masukkan nama lengkap"
                required
                autofocus
            />
            <flux:error name="nama" />

            <flux:input 
                wire:model="nik" 
                label="NIK" 
                placeholder="Masukkan NIK (16 digit angka)"
                required
                maxlength="16"
                type="text"
                pattern="[0-9]{16}"
            />

            <flux:textarea 
                wire:model="alamat" 
                label="Alamat" 
                placeholder="Masukkan alamat (opsional)"
                rows="3"
            />
            <flux:error name="alamat" />

            <flux:input 
                wire:model="nomor_hp" 
                label="Nomor HP" 
                placeholder="Masukkan nomor HP (opsional)"
                type="tel"
            />
            <flux:error name="nomor_hp" />

            <flux:select wire:model="jenis_kelamin" label="Jenis Kelamin">
                <option value="">Pilih Jenis Kelamin (Opsional)</option>
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
            </flux:select>
            <flux:error name="jenis_kelamin" />

            <flux:select wire:model="kelompok_id" label="Kelompok">
                <option value="">Pilih Kelompok (Opsional)</option>
                @foreach($kelompok as $k)
                    <option value="{{ $k->id }}">{{ $k->nama_kelompok }}</option>
                @endforeach
            </flux:select>
            <flux:error name="kelompok_id" />

            <flux:input 
                wire:model="tanggal_gabung" 
                type="date"
                label="Tanggal Gabung" 
                required
            />
            <flux:error name="tanggal_gabung" />

            <flux:select wire:model="status" label="Status" required>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
            </flux:select>
            <flux:error name="status" />

            <div class="flex items-center gap-4">
                <flux:button type="submit" variant="primary">
                    Simpan Perubahan
                </flux:button>
                <flux:button 
                    wire:navigate
                    href="{{ route('anggota.index') }}"
                    variant="ghost"
                >
                    Batal
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>

