<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex justify-between items-start">
        <div>
            <flux:heading size="xl">Aset Tetap</flux:heading>
            <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
                Registrasi aset tetap dengan penyusutan garis lurus otomatis tiap bulan
            </flux:heading>
        </div>
        @can('admin_desa')
            <div class="flex gap-2">
                <flux:button wire:click="prosesPenyusutan" size="sm"
                    wire:confirm="Proses penyusutan bulan berjalan untuk seluruh aset aktif?">
                    Proses Penyusutan Bulan Ini
                </flux:button>
                <flux:button wire:click="$set('showForm', true)" variant="primary" size="sm">Tambah Aset</flux:button>
            </div>
        @endcan
    </div>

    @if (session()->has('message'))
        <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">{{ session('message') }}</div>
    @endif

    @if($showForm)
        <flux:card class="p-6">
            <flux:heading size="sm" class="mb-4">Tambah Aset Tetap</flux:heading>
            <div class="grid md:grid-cols-3 gap-4">
                <flux:input wire:model="nama_aset" label="Nama Aset" placeholder="Contoh: Laptop Kantor" />
                <flux:input wire:model="tanggal_perolehan" label="Tanggal Perolehan" type="date" />
                <flux:input wire:model="harga_perolehan" label="Harga Perolehan (Rp)" type="number" />
                <flux:input wire:model="nilai_residu" label="Nilai Residu (Rp)" type="number" />
                <flux:input wire:model="umur_bulan" label="Umur Ekonomis (bulan)" type="number" />
                <flux:select wire:model="akun_aset_id" label="Akun Aset">
                    <option value="">- pilih -</option>
                    @foreach($akunAset as $a)<option value="{{ $a->id }}">{{ $a->kode_akun }} {{ $a->nama_akun }}</option>@endforeach
                </flux:select>
                <flux:select wire:model="akun_akumulasi_id" label="Akun Akumulasi Penyusutan">
                    <option value="">- pilih -</option>
                    @foreach($akunAset as $a)<option value="{{ $a->id }}">{{ $a->kode_akun }} {{ $a->nama_akun }}</option>@endforeach
                </flux:select>
                <flux:select wire:model="akun_beban_id" label="Akun Beban Penyusutan">
                    <option value="">- pilih -</option>
                    @foreach($akunBeban as $a)<option value="{{ $a->id }}">{{ $a->kode_akun }} {{ $a->nama_akun }}</option>@endforeach
                </flux:select>
            </div>
            @if($errors->any())
                <div class="mt-3 text-sm text-red-600">{{ $errors->first() }}</div>
            @endif
            <div class="mt-4 flex gap-2">
                <flux:button wire:click="simpan" variant="primary" size="sm">Simpan</flux:button>
                <flux:button wire:click="$set('showForm', false)" variant="ghost" size="sm">Batal</flux:button>
            </div>
        </flux:card>
    @endif

    <flux:card class="p-6 overflow-x-auto">
        <table class="min-w-full text-sm divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Aset</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Perolehan</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Harga</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Penyusutan/bln</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Akumulasi</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Nilai Buku</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Terakhir Disusutkan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($asets as $aset)
                    <tr>
                        <td class="px-4 py-2">{{ $aset->nama_aset }}</td>
                        <td class="px-4 py-2">{{ $aset->tanggal_perolehan->format('d/m/Y') }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($aset->harga_perolehan, 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($aset->penyusutan_bulanan, 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($aset->akumulasi_tercatat, 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right font-medium">{{ number_format($aset->nilai_buku, 0, ',', '.') }}</td>
                        <td class="px-4 py-2">{{ $aset->periode_penyusutan_terakhir ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-zinc-500">Belum ada aset tetap terdaftar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </flux:card>
</div>
