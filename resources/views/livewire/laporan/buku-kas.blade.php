<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold">Buku Kas USP</h2>
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
                        @if(auth()->user()->isAdminDesa())
                            <a href="{{ route('kas.saldo-awal') }}" 
                               wire:navigate
                               class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                                {{ $saldoAwalManual ? 'Edit Saldo Awal' : 'Input Saldo Awal' }}
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Alert jika belum ada saldo awal (khusus Admin Desa) -->
                @if(auth()->user()->isAdminDesa() && !$saldoAwalManual)
                    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-500 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <h3 class="text-sm font-semibold text-yellow-800">Saldo Awal Belum Diinput</h3>
                                <p class="text-sm text-yellow-700 mt-1">
                                    Anda belum menginput saldo awal kas dari sistem manual (Excel). 
                                    Saldo awal saat ini = Rp 0. 
                                    Silakan <a href="{{ route('kas.saldo-awal') }}" wire:navigate class="font-semibold underline">input saldo awal</a> 
                                    untuk perhitungan yang akurat.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Filter Bulan dan Tahun -->
                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="bulan" class="block text-sm font-medium text-gray-700 mb-2">Bulan</label>
                        <select wire:model.live="bulan" id="bulan" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Semua Bulan --</option>
                            @foreach ($bulanList as $item)
                                <option value="{{ $item['value'] }}">{{ $item['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tahun" class="block text-sm font-medium text-gray-700 mb-2">Tahun</label>
                        <select wire:model.live="tahun" id="tahun" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Pilih Tahun --</option>
                            @foreach ($tahunList as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Filter Wilayah (Super Admin & Admin Kecamatan) -->
                @if($user->hasKabupatenScope() || $user->isAdminKecamatan())
                    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if($user->hasKabupatenScope())
                            <div>
                                <label for="kecamatan_id" class="block text-sm font-medium text-gray-700 mb-2">Kecamatan</label>
                                <select wire:model.live="kecamatan_id" id="kecamatan_id" 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Semua Kecamatan --</option>
                                    @foreach ($kecamatanList as $kec)
                                        <option value="{{ $kec->id }}">{{ $kec->nama_kecamatan }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label for="desa_id" class="block text-sm font-medium text-gray-700 mb-2">Desa</label>
                            <select wire:model.live="desa_id" id="desa_id" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Semua Desa --</option>
                                @foreach ($desaList as $d)
                                    <option value="{{ $d->id }}">{{ $d->nama_desa }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                <!-- Saldo Awal -->
                <div class="mb-4 p-4 bg-blue-50 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700">Saldo Awal:</span>
                        <span class="text-lg font-bold text-blue-700">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Tabel Buku Kas -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    No
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tanggal
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Uraian Transaksi
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kas Masuk
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kas Keluar
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Saldo Kas
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($transaksi as $index => $item)
                                <tr class="{{ $item->jenis_transaksi === 'masuk' ? 'bg-green-50' : 'bg-red-50' }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $item->tanggal_transaksi->translatedFormat('d F Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $item->uraian }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ $item->jenis_transaksi === 'masuk' ? 'text-green-700 font-semibold' : 'text-gray-400' }}">
                                        @if ($item->jenis_transaksi === 'masuk')
                                            Rp {{ number_format($item->jumlah, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ $item->jenis_transaksi === 'keluar' ? 'text-red-700 font-semibold' : 'text-gray-400' }}">
                                        @if ($item->jenis_transaksi === 'keluar')
                                            Rp {{ number_format($item->jumlah, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900">
                                        Rp {{ number_format($item->saldo_berjalan, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                        Tidak ada transaksi pada periode ini
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($transaksi->count() > 0)
                            <tfoot class="bg-gray-100">
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-sm font-bold text-gray-900">
                                        Total
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right font-bold text-green-700">
                                        Rp {{ number_format($transaksi->where('jenis_transaksi', 'masuk')->sum('jumlah'), 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right font-bold text-red-700">
                                        Rp {{ number_format($transaksi->where('jenis_transaksi', 'keluar')->sum('jumlah'), 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right font-bold text-gray-900">
                                        Rp {{ number_format($transaksi->last()->saldo_berjalan ?? $saldoAwal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                <!-- Keterangan -->
                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Keterangan:</h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• <span class="font-medium">Kas Keluar</span>: Otomatis tercatat saat pinjaman dibuat (pencairan)</li>
                        <li>• <span class="font-medium">Kas Masuk</span>: Otomatis tercatat dari pembayaran angsuran</li>
                        <li>• <span class="font-medium">Saldo Kas Berjalan</span>: Dihitung otomatis dari transaksi</li>
                        <li>• Buku kas ini bersifat <span class="font-semibold">READ-ONLY</span>, tidak ada input manual</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
