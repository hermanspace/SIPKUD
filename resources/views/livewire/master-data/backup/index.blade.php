<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <!-- Header -->
                <div class="mb-6 flex justify-between items-start">
                    <div>
                        <flux:heading size="xl">Backup & Restore Database</flux:heading>
                        <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
                            Backup penuh database, unduh, unggah, dan pulihkan langsung dari panel
                        </flux:heading>
                    </div>
                    <flux:button wire:click="createBackup" wire:loading.attr="disabled" variant="primary">
                        <span wire:loading.remove wire:target="createBackup">Buat Backup Sekarang</span>
                        <span wire:loading wire:target="createBackup">Membuat backup...</span>
                    </flux:button>
                </div>

                <!-- Flash Messages -->
                @if (session()->has('message'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                        {{ session('message') }}
                    </div>
                @endif
                @if (session()->has('error'))
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Info -->
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg text-sm">
                    <p class="font-medium">Cara kerja</p>
                    <ul class="list-disc ml-5 mt-1 space-y-1">
                        <li>Backup otomatis dibuat setiap malam (01:30) dan disimpan 14 terbaru.</li>
                        <li><strong>Restore mengganti seluruh isi database</strong> dengan isi file backup. Sebelum restore, sistem otomatis membuat <em>safety snapshot</em> kondisi saat ini.</li>
                        <li>Setelah restore, verifikasi integritas akuntansi dijalankan otomatis.</li>
                        <li>Unduh backup secara berkala dan simpan di luar server.</li>
                    </ul>
                </div>

                <!-- Upload -->
                <div class="mb-6 p-4 border border-gray-200 rounded-lg">
                    <flux:heading size="sm" class="mb-2">Unggah file backup</flux:heading>
                    <div class="flex items-center gap-3">
                        <input type="file" wire:model="uploadFile" accept=".dump,.gz"
                            class="text-sm text-gray-600 file:mr-3 file:px-3 file:py-1.5 file:rounded file:border-0 file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200" />
                        <flux:button wire:click="upload" wire:loading.attr="disabled" size="sm">
                            <span wire:loading.remove wire:target="upload,uploadFile">Unggah</span>
                            <span wire:loading wire:target="upload,uploadFile">Mengunggah...</span>
                        </flux:button>
                    </div>
                    @error('uploadFile')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-gray-500">
                        Format: <code>.dump</code> (PostgreSQL) atau <code>.sql.gz</code> (MySQL). Maksimal 500 MB.
                    </p>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama File</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ukuran</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dibuat</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($backups as $backup)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">{{ $backup['filename'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $backup['size'] >= 1048576
                                            ? number_format($backup['size'] / 1048576, 2).' MB'
                                            : number_format($backup['size'] / 1024, 1).' KB' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $backup['created_at']->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right space-x-2">
                                        <flux:button size="xs" wire:click="download('{{ $backup['filename'] }}')">Unduh</flux:button>
                                        <flux:button size="xs" variant="danger" wire:click="confirmRestore('{{ $backup['filename'] }}')">Restore</flux:button>
                                        <flux:button size="xs" variant="ghost" wire:click="delete('{{ $backup['filename'] }}')"
                                            wire:confirm="Hapus file backup {{ $backup['filename'] }}?">Hapus</flux:button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                                        Belum ada file backup. Klik "Buat Backup Sekarang" untuk membuat yang pertama.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Restore -->
    @if ($restoreTarget)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:keydown.escape="cancelRestore">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 p-6">
                <flux:heading size="lg" class="text-red-700">Konfirmasi Restore Database</flux:heading>

                <div class="mt-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm space-y-2">
                    <p><strong>Seluruh data saat ini akan DIGANTI</strong> dengan isi file:</p>
                    <p class="font-mono">{{ $restoreTarget }}</p>
                    <p>Sistem akan masuk mode maintenance beberapa saat. Safety snapshot kondisi
                       sekarang dibuat otomatis sebelum restore, sehingga aksi ini dapat dibatalkan
                       dengan me-restore snapshot tersebut.</p>
                </div>

                <div class="mt-4">
                    <flux:input wire:model="confirmText" label='Ketik "RESTORE" untuk melanjutkan'
                        placeholder="RESTORE" autocomplete="off" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <flux:button variant="ghost" wire:click="cancelRestore">Batal</flux:button>
                    <flux:button variant="danger" wire:click="restore" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="restore">Restore Sekarang</span>
                        <span wire:loading wire:target="restore">Memulihkan database...</span>
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
