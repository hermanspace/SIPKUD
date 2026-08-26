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

    <div class="flex justify-between items-start">
        <div>
            <flux:heading size="xl">Master Pinjaman</flux:heading>
            <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
                Kelola data pinjaman anggota USP/UED-SP
            </flux:heading>
        </div>
        <div class="flex gap-2">
            <!-- Export Buttons -->
            <button wire:click="exportExcel" 
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export Excel
            </button>
            <button wire:click="exportPdf" 
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                Export PDF
            </button>
        </div>
    </div>

    <flux:card class="p-6">
        <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-col gap-4 sm:flex-row">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari nomor pinjaman atau nama anggota..."
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
                    <option value="lunas">Lunas</option>
                </flux:select>
            </div>
            @if(auth()->user()->isAdminDesa())
                <flux:button 
                    wire:navigate 
                    href="{{ route('pinjaman.create') }}" 
                    variant="primary"
                >
                    Tambah Pinjaman
                </flux:button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-2 py-2 text-left text-xs font-semibold w-12">No</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold min-w-[150px]">Nomor Pinjaman</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold min-w-[150px]">Anggota</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold min-w-[100px]">Sektor Usaha</th>
                        @if(auth()->user()->hasKabupatenScope())
                            <th class="px-2 py-2 text-left text-xs font-semibold min-w-[120px]">Kecamatan</th>
                            <th class="px-2 py-2 text-left text-xs font-semibold min-w-[120px]">Desa</th>
                        @endif
                        <th class="px-2 py-2 text-left text-xs font-semibold w-28">Tanggal</th>
                        <th class="px-2 py-2 text-right text-xs font-semibold w-32">Jumlah</th>
                        <th class="px-2 py-2 text-center text-xs font-semibold w-24">Jangka Waktu</th>
                        <th class="px-2 py-2 text-right text-xs font-semibold w-24">Jasa %</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold w-20">Status</th>
                        <th class="px-2 py-2 text-right text-xs font-semibold w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pinjaman as $item)
                        <tr class="border-b border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                            <td class="px-2 py-2 text-xs">{{ $pinjaman->firstItem() + $loop->index }}</td>
                            <td class="px-2 py-2">
                                <a href="{{ route('pinjaman.show', $item->id) }}" wire:navigate class="font-medium text-sm text-blue-600 hover:text-blue-800 hover:underline">{{ $item->nomor_pinjaman }}</a>
                            </td>
                            <td class="px-2 py-2">
                                <a href="{{ route('pinjaman.show', $item->id) }}" wire:navigate class="font-medium text-sm text-blue-600 hover:text-blue-800 hover:underline">{{ $item->anggota->nama }}</a>
                            </td>
                            <td class="px-2 py-2 text-xs">
                                {{ $item->sektorUsaha?->nama ?? '-' }}
                            </td>
                            @if(auth()->user()->hasKabupatenScope())
                                <td class="px-2 py-2 text-xs">
                                    <div class="truncate max-w-[120px]" title="{{ $item->desa->kecamatan->nama_kecamatan ?? '-' }}">
                                        {{ $item->desa->kecamatan->nama_kecamatan ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-2 py-2 text-xs">
                                    <div class="truncate max-w-[120px]" title="{{ $item->desa->nama_desa ?? '-' }}">
                                        {{ $item->desa->nama_desa ?? '-' }}
                                    </div>
                                </td>
                            @endif
                            <td class="px-2 py-2 text-xs">
                                {{ $item->tanggal_pinjaman ? $item->tanggal_pinjaman->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-2 py-2 text-right text-xs">
                                Rp {{ number_format($item->jumlah_pinjaman, 0, ',', '.') }}
                            </td>
                            <td class="px-2 py-2 text-center text-xs">
                                {{ $item->jangka_waktu_bulan }} bulan
                            </td>
                            <td class="px-2 py-2 text-right text-xs">
                                {{ number_format($item->jasa_persen, 2, ',', '.') }}%
                            </td>
                            <td class="px-2 py-2">
                                <flux:badge size="sm" :variant="$item->status_pinjaman === 'aktif' ? 'success' : 'primary'">
                                    {{ ucfirst($item->status_pinjaman) }}
                                </flux:badge>
                            </td>
                            <td class="px-2 py-2 text-right">
                                @if(auth()->user()->isAdminDesa())
                                    <div class="flex items-center justify-end gap-1">
                                        <flux:button 
                                            wire:navigate
                                            :href="route('pinjaman.edit', $item->id)"
                                            variant="ghost"
                                            size="sm"
                                            class="!p-1"
                                        >
                                            <flux:icon.pencil class="size-3.5" />
                                        </flux:button>
                                        <flux:button 
                                            wire:click="delete({{ $item->id }})"
                                            wire:confirm="Apakah Anda yakin ingin menghapus pinjaman ini?"
                                            variant="ghost"
                                            size="sm"
                                            class="!p-1 text-red-600 hover:text-red-700 dark:text-red-400"
                                        >
                                            <flux:icon.trash class="size-3.5" />
                                        </flux:button>
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->hasKabupatenScope() ? '12' : '10' }}" class="px-2 py-8 text-center text-xs text-zinc-600 dark:text-zinc-400">
                                Tidak ada data pinjaman ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pinjaman->hasPages())
            <div class="mt-4">
                {{ $pinjaman->links() }}
            </div>
        @endif
    </flux:card>
</div>
