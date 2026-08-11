<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl">Tambah Pengguna</flux:heading>
        <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
            Tambah pengguna baru ke sistem
        </flux:heading>
    </div>

    <flux:card class="p-6">
        <form wire:submit="save" class="space-y-6">
            <flux:input 
                wire:model="nama" 
                label="Nama" 
                placeholder="Masukkan nama lengkap"
                required
                autofocus
            />
            <flux:error name="nama" />

            <flux:input 
                wire:model="email" 
                label="Email" 
                type="email"
                placeholder="email@example.com"
                required
            />
            <flux:error name="email" />

            <flux:input 
                wire:model="password" 
                label="Password" 
                type="password"
                placeholder="Minimal 8 karakter"
                required
            />
            <flux:error name="password" />

            <flux:input 
                wire:model="password_confirmation" 
                label="Konfirmasi Password" 
                type="password"
                placeholder="Ulangi password"
                required
            />
            <flux:error name="password_confirmation" />

            @if($isAdminKecamatan)
                <flux:select wire:model.live="role" label="Role" required disabled>
                    <option value="admin_desa">Admin Desa</option>
                </flux:select>
            @else
                @php
                    $roleLabels = \App\Models\User::ROLE_LABELS;
                @endphp
                <flux:select wire:model.live="role" label="Role" required>
                    @foreach(auth()->user()->manageableRoles() as $r)
                        <option value="{{ $r }}">{{ $roleLabels[$r] ?? $r }}</option>
                    @endforeach
                </flux:select>
            @endif
            <flux:error name="role" />
            @if($isAdminKecamatan)
                <flux:text class="text-xs text-zinc-500">
                    Anda hanya dapat membuat Admin Desa
                </flux:text>
            @endif

            @if(!in_array($role, ['super_admin', 'admin_kabupaten']))
                @if($isAdminKecamatan)
                    <flux:select wire:model.live="kecamatan_id" label="Kecamatan" required disabled>
                        <option value="">Pilih Kecamatan</option>
                        @foreach($kecamatan as $kec)
                            <option value="{{ $kec->id }}">{{ $kec->nama_kecamatan }}</option>
                        @endforeach
                    </flux:select>
                @else
                    <flux:select wire:model.live="kecamatan_id" label="Kecamatan" required>
                        <option value="">Pilih Kecamatan</option>
                        @foreach($kecamatan as $kec)
                            <option value="{{ $kec->id }}">{{ $kec->nama_kecamatan }}</option>
                        @endforeach
                    </flux:select>
                @endif
                <flux:error name="kecamatan_id" />
                @if($isAdminKecamatan)
                    <flux:text class="text-xs text-zinc-500">
                        Kecamatan sudah ditetapkan berdasarkan akun Anda
                    </flux:text>
                @endif

                @if($kecamatan_id && $role !== 'admin_kecamatan')
                    <flux:select wire:model="desa_id" label="Desa" required>
                        <option value="">Pilih Desa</option>
                        @foreach($desa as $d)
                            <option value="{{ $d->id }}">{{ $d->nama_desa }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="desa_id" />
                @endif
            @endif

            <div class="flex items-center gap-4">
                <flux:button type="submit" variant="primary">
                    Simpan
                </flux:button>
                <flux:button 
                    :href="route('pengguna.index')" 
                    variant="ghost"
                    wire:navigate
                >
                    Batal
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
