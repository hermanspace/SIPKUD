<div class="flex h-full w-full flex-1 flex-col gap-6">

    <div>
        <flux:heading size="xl">Pengaturan Sistem</flux:heading>
        <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
            Konfigurasi pengaturan global sistem SIPKUD
        </flux:heading>
    </div>

    <flux:card class="p-6">
    <form wire:submit="update" enctype="multipart/form-data" class="space-y-6">            <div class="grid gap-6 md:grid-cols-2">
                <flux:input 
                    wire:model="nama_instansi" 
                    label="Nama Instansi" 
                    placeholder="Contoh: Dinas PMD"
                    required
                    autofocus
                />
                <flux:error name="nama_instansi" />

                <flux:input 
                    wire:model="nama_daerah" 
                    label="Nama Daerah" 
                    placeholder="Contoh: Kabupaten Purwakarta"
                    required
                />
                <flux:error name="nama_daerah" />
            </div>

            <flux:input 
                wire:model="base_title" 
                label="Judul Halaman (Base Title)" 
                placeholder="Contoh: SIPKUD - Sistem Informasi Pelaporan Keuangan USP Desa"
            />
            <flux:error name="base_title" />

            <flux:textarea 
                wire:model="alamat" 
                label="Alamat" 
                placeholder="Masukkan alamat instansi"
                rows="3"
            />
            <flux:error name="alamat" />

            <flux:input 
                wire:model="telepon" 
                label="Telepon" 
                placeholder="Contoh: 0264-123456"
            />
            <flux:error name="telepon" />

            <div>
                <flux:input 
                    wire:model="persentase_shu" 
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    label="Persentase SHU (%)" 
                    placeholder="Contoh: 20"
                    required
                />
                <flux:error name="persentase_shu" />
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Persentase Sisa Hasil Usaha yang dihitung dari total pendapatan (jasa + denda). 
                    Nilai antara 0-100%.
                </p>
            </div>

            <flux:input 
                wire:model="warna_tema" 
                label="Warna Tema (Hex Code)" 
                placeholder="Contoh: #3B82F6"
            />
            <flux:error name="warna_tema" />

            <div class="space-y-4">
                <div>
                    <flux:heading size="sm" class="mb-2">Logo Instansi</flux:heading>
                    @if($logo_instansi_path)
                        <div class="mb-2 flex items-center gap-4">
                            <img src="{{ asset('storage/' . $logo_instansi_path) }}" alt="Logo" class="h-16 w-auto border border-zinc-200 dark:border-zinc-700 rounded p-2">
                            <flux:button 
                                type="button"
                                wire:click="removeLogo"
                                wire:confirm="Apakah Anda yakin ingin menghapus logo?"
                                variant="ghost"
                                size="sm"
                                class="text-red-600"
                            >
                                Hapus Logo
                            </flux:button>
                        </div>
                    @endif
                    @if($logo_instansi)
                        <div class="mb-2 p-3 bg-zinc-50 dark:bg-zinc-900 rounded border border-zinc-200 dark:border-zinc-700">
                            <p class="text-sm font-medium mb-1">File yang dipilih:</p>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $logo_instansi->getClientOriginalName() }}</p>
                            <p class="text-xs text-zinc-500 mt-1">Ukuran: {{ number_format($logo_instansi->getSize() / 1024, 2) }} KB</p>
                        </div>
                    @endif
                    <flux:input 
                        wire:model.live="logo_instansi" 
                        type="file"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        label="Upload Logo Baru"
                    />
                    <flux:text class="mt-1 text-xs text-zinc-500">
                        Format: JPG, PNG, GIF, WEBP. Maksimal 2MB
                    </flux:text>
                    <flux:error name="logo_instansi" />
                </div>

                <div>
                    <flux:heading size="sm" class="mb-2">Favicon</flux:heading>
                    @if($favicon_path)
                        <div class="mb-2 flex items-center gap-4">
                            <img src="{{ asset('storage/' . $favicon_path) }}" alt="Favicon" class="h-8 w-8 border border-zinc-200 dark:border-zinc-700 rounded p-1">
                            <flux:button 
                                type="button"
                                wire:click="removeFavicon"
                                wire:confirm="Apakah Anda yakin ingin menghapus favicon?"
                                variant="ghost"
                                size="sm"
                                class="text-red-600"
                            >
                                Hapus Favicon
                            </flux:button>
                        </div>
                    @endif
                    @if($favicon)
                        <div class="mb-2 p-3 bg-zinc-50 dark:bg-zinc-900 rounded border border-zinc-200 dark:border-zinc-700">
                            <p class="text-sm font-medium mb-1">File yang dipilih:</p>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $favicon->getClientOriginalName() }}</p>
                            <p class="text-xs text-zinc-500 mt-1">Ukuran: {{ number_format($favicon->getSize() / 1024, 2) }} KB</p>
                        </div>
                    @endif
                    <flux:input 
                        wire:model.live="favicon" 
                        type="file"
                        accept="image/jpeg,image/png,image/gif,image/x-icon,image/vnd.microsoft.icon"
                        label="Upload Favicon Baru"
                    />
                    <flux:text class="mt-1 text-xs text-zinc-500">
                        Format: JPG, PNG, GIF, ICO. Maksimal 512KB. Ukuran disarankan: 32x32 atau 16x16
                    </flux:text>
                    <flux:error name="favicon" />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <flux:button type="submit" variant="primary">
                    Simpan Pengaturan
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
