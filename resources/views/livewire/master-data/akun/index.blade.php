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
        <flux:heading size="xl">Master Akun</flux:heading>
        <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
            Chart of Accounts (COA) global untuk seluruh desa. Hanya Admin dan Super Admin yang dapat menambah/mengedit.
        </flux:heading>
    </div>

    <flux:card class="p-6">
        <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-col gap-4 sm:flex-row">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari kode atau nama akun..."
                    class="w-full sm:w-64"
                />
                <flux:select wire:model.live="tipeFilter" class="w-full sm:w-48">
                    <option value="">Semua Tipe</option>
                    <option value="aset">Aset</option>
                    <option value="kewajiban">Kewajiban</option>
                    <option value="ekuitas">Ekuitas</option>
                    <option value="pendapatan">Pendapatan</option>
                    <option value="beban">Beban</option>
                </flux:select>
                <flux:select wire:model.live="statusFilter" class="w-full sm:w-48">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </flux:select>
            </div>
            @if(auth()->user()->can('manage_akun'))
                <flux:button 
                    wire:navigate 
                    href="{{ route('akun.create') }}" 
                    variant="primary"
                >
                    Tambah Akun
                </flux:button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-4 py-3 text-left text-sm font-semibold">No</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Kode Akun</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Nama Akun</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Tipe Akun</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Status</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($akun as $item)
                        <tr class="border-b border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                            <td class="px-4 py-3 text-sm">{{ $akun->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 text-sm">
                                <div class="font-mono font-medium">{{ $item->kode_akun }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium">{{ $item->nama_akun }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <flux:badge>{{ ucfirst($item->tipe_akun) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <flux:badge :variant="$item->status === 'aktif' ? 'success' : 'danger'">
                                    {{ ucfirst($item->status) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if(auth()->user()->can('manage_akun'))
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button 
                                            wire:navigate
                                            :href="route('akun.edit', $item->id)"
                                            variant="ghost"
                                            size="sm"
                                        >
                                            <flux:icon.pencil class="size-4" />
                                        </flux:button>
                                        <flux:button 
                                            wire:click="delete({{ $item->id }})"
                                            wire:confirm="Apakah Anda yakin ingin menghapus akun ini?"
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
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-zinc-600 dark:text-zinc-400">
                                Tidak ada data akun ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($akun->hasPages())
            <div class="mt-4">
                {{ $akun->links() }}
            </div>
        @endif
    </flux:card>
</div>

