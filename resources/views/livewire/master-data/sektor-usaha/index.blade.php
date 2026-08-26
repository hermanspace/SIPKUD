<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl">Master Sektor Usaha</flux:heading>
        <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
            Kelola sektor usaha untuk pinjaman (mis. Pertanian, Perdagangan)
        </flux:heading>
    </div>

    <flux:card class="p-6">
        <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-1 flex-wrap items-end gap-4">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama atau keterangan..." class="w-full sm:w-56" />
                @if($desa->isNotEmpty())
                    <flux:select wire:model.live="desaFilter" class="w-full sm:w-48">
                        <option value="">Semua Desa</option>
                        @foreach($desa as $d)
                            <option value="{{ $d->id }}">{{ $d->nama_desa }}</option>
                        @endforeach
                    </flux:select>
                @endif
                <flux:select wire:model.live="statusFilter" class="w-full sm:w-40">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </flux:select>
            </div>
        </div>

        @if(auth()->user()->isAdminDesa())
            <div class="mb-6 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <flux:heading size="sm" class="mb-3">Tambah Sektor Usaha Baru</flux:heading>
                <form wire:submit="save" class="flex flex-wrap items-end gap-3">
                    <flux:input wire:model="nama" label="Nama Sektor" placeholder="Contoh: Pertanian" required class="min-w-[180px]" />
                    <flux:input wire:model="keterangan" label="Keterangan (opsional)" placeholder="Opsional" class="min-w-[200px]" />
                    <flux:button type="submit" variant="primary">Tambah</flux:button>
                </form>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-4 py-3 text-left text-sm font-semibold">No</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Nama</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Keterangan</th>
                        @if($desa->isNotEmpty())
                            <th class="px-4 py-3 text-left text-sm font-semibold">Desa</th>
                        @endif
                        <th class="px-4 py-3 text-left text-sm font-semibold">Digunakan (Pinjaman)</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Status</th>
                        @if(auth()->user()->isAdminDesa())
                            <th class="px-4 py-3 text-right text-sm font-semibold">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($sektorUsaha as $item)
                        <tr class="border-b border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                            <td class="px-4 py-3 text-sm">{{ $sektorUsaha->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 text-sm font-medium">{{ $item->nama }}</td>
                            <td class="px-4 py-3 text-sm max-w-xs truncate text-zinc-600 dark:text-zinc-400">{{ $item->keterangan ?? '-' }}</td>
                            @if($desa->isNotEmpty())
                                <td class="px-4 py-3 text-sm">{{ $item->desa->nama_desa ?? '-' }}</td>
                            @endif
                            <td class="px-4 py-3 text-sm"><flux:badge>{{ $item->pinjaman_count }}</flux:badge></td>
                            <td class="px-4 py-3 text-sm">
                                <flux:badge :variant="$item->status === 'aktif' ? 'success' : 'danger'">{{ ucfirst($item->status) }}</flux:badge>
                            </td>
                            @if(auth()->user()->isAdminDesa())
                                <td class="px-4 py-3 text-right">
                                    <flux:button
                                        wire:click="delete({{ $item->id }})"
                                        wire:confirm="Hapus sektor usaha ini? Hanya bisa jika tidak ada pinjaman yang menggunakannya."
                                        variant="ghost"
                                        size="sm"
                                        class="text-red-600 hover:text-red-700 dark:text-red-400"
                                    >
                                        <flux:icon.trash class="size-4" />
                                    </flux:button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $desa->isNotEmpty() ? 7 : 5 }}" class="px-4 py-8 text-center text-sm text-zinc-600 dark:text-zinc-400">
                                Belum ada sektor usaha. @if(auth()->user()->isAdminDesa()) Tambah dari form di atas atau saat membuat pinjaman. @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sektorUsaha->hasPages())
            <div class="mt-4">{{ $sektorUsaha->links() }}</div>
        @endif
    </flux:card>
</div>
