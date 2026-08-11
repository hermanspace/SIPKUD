<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Master Pengguna</flux:heading>
            <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
                @if(auth()->user()->isSuperAdmin())
                    Kelola seluruh pengguna sistem (semua role)
                @elseif(auth()->user()->isAdminKabupaten())
                    Kelola pengguna sistem (selain Super Admin)
                @elseif(auth()->user()->isAdminKecamatan())
                    Kelola admin desa di kecamatan Anda
                @else
                    Kelola pengguna sistem
                @endif
            </flux:heading>
        </div>
        <flux:button :href="route('pengguna.create')" variant="primary" wire:navigate>
            <flux:icon.plus class="size-4" />
            Tambah Pengguna
        </flux:button>
    </div>

    <flux:card class="p-6">
        <div class="mb-4 flex flex-col gap-4 sm:flex-row">
            <div class="flex-1">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari nama atau email..."
                    icon="magnifying-glass"
                />
            </div>
            @if(auth()->user()->hasKabupatenScope())
                <div class="w-full sm:w-48">
                    <flux:select wire:model.live="roleFilter" placeholder="Filter Role">
                        <option value="">Semua Role</option>
                        @if(auth()->user()->isSuperAdmin())
                            <option value="super_admin">Super Admin</option>
                        @endif
                        <option value="admin_kabupaten">Admin Kabupaten</option>
                        <option value="admin_kecamatan">Admin Kecamatan</option>
                        <option value="admin_desa">Admin Desa</option>
                        <option value="executive_view">Executive View</option>
                    </flux:select>
                </div>
                <div class="w-full sm:w-48">
                    <flux:select wire:model.live="kecamatanFilter" placeholder="Filter Kecamatan">
                        <option value="">Semua Kecamatan</option>
                        @foreach($kecamatan as $kec)
                            <option value="{{ $kec->id }}">{{ $kec->nama_kecamatan }}</option>
                        @endforeach
                    </flux:select>
                </div>
                @if($kecamatanFilter)
                    <div class="w-full sm:w-48">
                        <flux:select wire:model.live="desaFilter" placeholder="Filter Desa">
                            <option value="">Semua Desa</option>
                            @foreach($desa as $d)
                                <option value="{{ $d->id }}">{{ $d->nama_desa }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                @endif
            @elseif(auth()->user()->isAdminKecamatan())
                <div class="w-full sm:w-48">
                    <flux:select wire:model.live="desaFilter" placeholder="Filter Desa">
                        <option value="">Semua Desa</option>
                        @foreach($desa as $d)
                            <option value="{{ $d->id }}">{{ $d->nama_desa }}</option>
                        @endforeach
                    </flux:select>
                </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-4 py-3 text-left text-sm font-semibold">No</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Nama</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Email</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Role</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Kecamatan</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Desa</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="border-b border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                            <td class="px-4 py-3 text-sm">{{ $users->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 text-sm">{{ $user->nama }}</td>
                            <td class="px-4 py-3 text-sm">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-sm">
                                <flux:badge :variant="match($user->role) {
                                    'super_admin' => 'primary',
                                    'admin_kabupaten' => 'primary',
                                    'admin_kecamatan' => 'warning',
                                    'admin_desa' => 'success',
                                    'executive_view' => 'info',
                                    default => 'secondary'
                                }">
                                    {{ \App\Models\User::ROLE_LABELS[$user->role] ?? $user->role }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $user->kecamatan?->nama_kecamatan ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $user->desa?->nama_desa ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button 
                                        :href="route('pengguna.edit', $user->id)" 
                                        variant="ghost" 
                                        size="sm"
                                        wire:navigate
                                    >
                                        <flux:icon.pencil class="size-4" />
                                    </flux:button>
                                    @if($user->id !== auth()->id())
                                        <flux:button 
                                            wire:click="delete({{ $user->id }})"
                                            wire:confirm="Apakah Anda yakin ingin menghapus pengguna ini?"
                                            variant="ghost" 
                                            size="sm"
                                            class="text-red-600 hover:text-red-700 dark:text-red-400"
                                        >
                                            <flux:icon.trash class="size-4" />
                                        </flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-600 dark:text-zinc-400">
                                Tidak ada data pengguna ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="mt-4">
                {{ $users->links() }}
            </div>
        @endif
    </flux:card>
</div>
