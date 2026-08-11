<div class="flex h-full w-full flex-1 flex-col gap-6" 
     x-data="{ 
         showSuccess: false, 
         showError: false, 
         successMessage: '', 
         errorMessage: '' 
     }"
     x-init="
         $wire.on('success', (event) => {
             successMessage = event.message || 'Berhasil!';
             showSuccess = true;
             setTimeout(() => showSuccess = false, 3000);
         });
         $wire.on('error', (event) => {
             errorMessage = event.message || 'Terjadi kesalahan!';
             showError = true;
             setTimeout(() => showError = false, 5000);
         });
     ">
    <!-- Success Notification -->
    <div x-show="showSuccess" 
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed top-4 right-4 z-50 max-w-sm">
        <flux:callout variant="success" icon="check-circle">
            <span x-text="successMessage"></span>
        </flux:callout>
    </div>

    <!-- Error Notification -->
    <div x-show="showError" 
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed top-4 right-4 z-50 max-w-sm">
        <flux:callout variant="danger" icon="x-circle">
            <span x-text="errorMessage"></span>
        </flux:callout>
    </div>

    <div>
        <flux:heading size="xl">Master Kelompok</flux:heading>
        <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
            Kelola data kelompok untuk mengkategorikan anggota
        </flux:heading>
    </div>

    <flux:card class="p-6">
        <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-col gap-4 sm:flex-row">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari nama kelompok atau keterangan..."
                    class="w-full sm:w-64"
                />
                @if(auth()->user()->hasKabupatenScope())
                    <flux:select wire:model.live="kecamatanFilter" class="w-full sm:w-48">
                        <option value="">Semua Kecamatan</option>
                        @foreach($kecamatan as $kec)
                            <option value="{{ $kec->id }}">{{ $kec->nama_kecamatan }}</option>
                        @endforeach
                    </flux:select>
                    @if($kecamatanFilter)
                        <flux:select wire:model.live="desaFilter" class="w-full sm:w-48">
                            <option value="">Semua Desa</option>
                            @foreach($desa as $d)
                                <option value="{{ $d->id }}">{{ $d->nama_desa }}</option>
                            @endforeach
                        </flux:select>
                    @endif
                @elseif(auth()->user()->isAdminKecamatan())
                    <flux:select wire:model.live="desaFilter" class="w-full sm:w-48">
                        <option value="">Semua Desa</option>
                        @foreach($desa as $d)
                            <option value="{{ $d->id }}">{{ $d->nama_desa }}</option>
                        @endforeach
                    </flux:select>
                @endif
                <flux:select wire:model.live="statusFilter" class="w-full sm:w-48">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </flux:select>
            </div>
            @if(auth()->user()->isAdminDesa())
                <flux:button 
                    wire:navigate 
                    href="{{ route('kelompok.create') }}" 
                    variant="primary"
                >
                    Tambah Kelompok
                </flux:button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-4 py-3 text-left text-sm font-semibold">No</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Nama Kelompok</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Keterangan</th>
                        @if(auth()->user()->hasKabupatenScope())
                            <th class="px-4 py-3 text-left text-sm font-semibold">Kecamatan</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Desa</th>
                        @endif
                        <th class="px-4 py-3 text-left text-sm font-semibold">Jumlah Anggota</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Status</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kelompok as $item)
                        <tr class="border-b border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                            <td class="px-4 py-3 text-sm">{{ $kelompok->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium">{{ $item->nama_kelompok }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="max-w-xs truncate text-zinc-600 dark:text-zinc-400">
                                    {{ $item->keterangan ?? '-' }}
                                </div>
                            </td>
                            @if(auth()->user()->hasKabupatenScope())
                                <td class="px-4 py-3 text-sm">
                                    {{ $item->desa->kecamatan->nama_kecamatan ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ $item->desa->nama_desa ?? '-' }}
                                </td>
                            @endif
                            <td class="px-4 py-3 text-sm">
                                <flux:badge>{{ $item->anggota_count }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <flux:badge :variant="$item->status === 'aktif' ? 'success' : 'danger'">
                                    {{ ucfirst($item->status) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if(auth()->user()->isAdminDesa())
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button 
                                            wire:navigate
                                            :href="route('kelompok.edit', $item->id)"
                                            variant="ghost"
                                            size="sm"
                                        >
                                            <flux:icon.pencil class="size-4" />
                                        </flux:button>
                                        <flux:button 
                                            wire:click="delete({{ $item->id }})"
                                            wire:confirm="Apakah Anda yakin ingin menghapus kelompok ini?"
                                            variant="ghost"
                                            size="sm"
                                            class="text-red-600 hover:text-red-700 dark:text-red-400"
                                        >
                                            <flux:icon.trash class="size-4" />
                                        </flux:button>
                                    </div>
                                @else
                                    <span class="text-sm text-zinc-500 dark:text-zinc-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->hasKabupatenScope() ? '8' : '6' }}" class="px-4 py-8 text-center text-sm text-zinc-600 dark:text-zinc-400">
                                Tidak ada data kelompok ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($kelompok->hasPages())
            <div class="mt-4">
                {{ $kelompok->links() }}
            </div>
        @endif
    </flux:card>
</div>

