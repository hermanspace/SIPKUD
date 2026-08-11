<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl">Edit Pengguna</flux:heading>
        <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
            Edit data pengguna: {{ $user->nama }}
        </flux:heading>
    </div>

    <flux:card class="p-6">
        <form wire:submit="update" class="space-y-6">
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

            <div>
                <flux:input 
                    wire:model="password" 
                    label="Password Baru (kosongkan jika tidak ingin mengubah)" 
                    type="password"
                    placeholder="Minimal 8 karakter"
                />
                <flux:text class="mt-1 text-xs text-zinc-500">
                    Kosongkan jika tidak ingin mengubah password
                </flux:text>
                <flux:error name="password" />
            </div>

            <?php if ($password): ?>
                <flux:input 
                    wire:model="password_confirmation" 
                    label="Konfirmasi Password Baru" 
                    type="password"
                    placeholder="Ulangi password baru"
                />
                <flux:error name="password_confirmation" />
            <?php endif; ?>

            <label class="flux-label block text-sm font-medium text-zinc-700 dark:text-zinc-300">Role</label>
            @php
                $roleLabels = \App\Models\User::ROLE_LABELS;
            @endphp
            <select wire:model.live="role" required {{ $isAdminKecamatan ? 'disabled' : '' }} class="flux-input block w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                @foreach(auth()->user()->manageableRoles() as $r)
                    <option value="{{ $r }}">{{ $roleLabels[$r] ?? $r }}</option>
                @endforeach
            </select>
            @error('role')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            @if($isAdminKecamatan)
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Anda hanya dapat mengubah menjadi Admin Desa.</p>
            @endif

            @php
            $showDesaSelect = $kecamatan_id && $role !== 'admin_kecamatan';
            @endphp
            @if(!in_array($role, ['super_admin', 'admin_kabupaten']))
            <div class="space-y-4">
                <label class="flux-label block text-sm font-medium text-zinc-700 dark:text-zinc-300">Kecamatan</label>
                <select wire:model.live="kecamatan_id" {{ $isAdminKecamatan ? 'disabled' : '' }} required class="flux-input block w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                    <option value="">Pilih Kecamatan</option>
                    @foreach($kecamatan as $kec)
                        <option value="{{ $kec->id }}">{{ $kec->nama_kecamatan }}</option>
                    @endforeach
                </select>
                @error('kecamatan_id')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                @if($isAdminKecamatan)
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Kecamatan sudah ditetapkan berdasarkan akun Anda.</p>
                @endif
                @if($showDesaSelect)
                <label class="flux-label block text-sm font-medium text-zinc-700 dark:text-zinc-300">Desa</label>
                <select wire:model="desa_id" required class="flux-input block w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                    <option value="">Pilih Desa</option>
                    @foreach($desa as $d)
                        <option value="{{ $d->id }}">{{ $d->nama_desa }}</option>
                    @endforeach
                </select>
                @error('desa_id')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                @endif
            </div>
            @endif

            <div class="flex items-center gap-4">
                <flux:button type="submit" variant="primary">
                    Perbarui
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
