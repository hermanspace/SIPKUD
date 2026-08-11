<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="mb-6 flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-semibold mb-2">Laporan Akhir USP</h2>
                        <p class="text-sm text-gray-600">
                            Rekapitulasi pendapatan, sisa pinjaman, dan perhitungan SHU (Sisa Hasil Usaha)
                        </p>
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

                <!-- Info Periode -->
                <div class="mb-6 p-4 bg-indigo-50 border border-indigo-200 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <h3 class="text-sm font-semibold text-indigo-900">Periode Laporan:</h3>
                            <p class="text-sm text-indigo-700">
                                @if ($bulan && $tahun)
                                    {{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') }} {{ $tahun }}
                                @elseif ($tahun)
                                    Tahun {{ $tahun }}
                                @else
                                    Semua Periode
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Rekapitulasi Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Pinjaman Tersalurkan -->
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium opacity-90">Pinjaman Tersalurkan</h3>
                            <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold">Rp {{ number_format($totalPinjamanTersalurkan, 0, ',', '.') }}</p>
                        <p class="text-xs opacity-80 mt-1">Dana yang disalurkan periode ini</p>
                    </div>

                    <!-- Total Pokok Terbayar -->
                    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium opacity-90">Pokok Terbayar</h3>
                            <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold">Rp {{ number_format($totalPokokTerbayar, 0, ',', '.') }}</p>
                        <p class="text-xs opacity-80 mt-1">Pokok yang sudah dibayar</p>
                    </div>

                    <!-- Sisa Pinjaman Aktif -->
                    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium opacity-90">Sisa Pinjaman Aktif</h3>
                            <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold">Rp {{ number_format($totalSisaPinjaman, 0, ',', '.') }}</p>
                        <p class="text-xs opacity-80 mt-1">{{ $jumlahPinjamanAktif }} pinjaman aktif</p>
                    </div>

                    <!-- Total Pendapatan -->
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium opacity-90">Total Pendapatan</h3>
                            <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</p>
                        <p class="text-xs opacity-80 mt-1">Jasa + Denda periode ini</p>
                    </div>
                </div>

                <!-- Detail Pendapatan -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-4">Detail Pendapatan</h3>
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Jenis Pendapatan
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Jumlah
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Persentase
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        Pendapatan Jasa
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-semibold">
                                        Rp {{ number_format($totalPendapatanJasa, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-600">
                                        {{ $totalPendapatan > 0 ? number_format(($totalPendapatanJasa / $totalPendapatan) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        Pendapatan Denda
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-semibold">
                                        Rp {{ number_format($totalPendapatanDenda, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-600">
                                        {{ $totalPendapatan > 0 ? number_format(($totalPendapatanDenda / $totalPendapatan) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-100">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        Total Pendapatan
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900">
                                        Rp {{ number_format($totalPendapatan, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900">
                                        100%
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Perhitungan SHU -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-4">Perhitungan SHU (Sisa Hasil Usaha)</h3>
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 rounded-lg p-6">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-700">Total Pendapatan (Jasa + Denda):</span>
                                <span class="text-lg font-semibold text-gray-900">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-700">Total Beban:</span>
                                <span class="text-lg font-semibold text-gray-900">(Rp {{ number_format($totalBeban, 0, ',', '.') }})</span>
                            </div>

                            <div class="border-t border-indigo-300 pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-base font-semibold text-indigo-900">SHU (Laba Bersih):</span>
                                    <span class="text-2xl font-bold {{ $totalShu < 0 ? 'text-red-600' : 'text-indigo-700' }}">Rp {{ number_format($totalShu, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            @if($totalShu > 0)
                                <div class="border-t border-indigo-200 pt-3">
                                    <p class="text-sm font-medium text-gray-700 mb-2">Simulasi Alokasi SHU (sesuai AD/ART):</p>
                                    @foreach($alokasiShu as $item)
                                        <div class="flex justify-between text-sm py-0.5">
                                            <span class="text-gray-600">{{ $item['nama'] }} ({{ $item['persen'] }}%)</span>
                                            <span>Rp {{ number_format($item['jumlah'], 0, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 p-3 bg-white rounded border border-indigo-200">
                            <p class="text-xs text-gray-600">
                                <strong>Catatan:</strong> SHU = total pendapatan dikurangi total beban periode ini (laba bersih).
                                Persentase alokasi mengikuti konfigurasi AD/ART; pembagian aktual ditetapkan Musyawarah Desa.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Keterangan -->
                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Keterangan:</h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• <span class="font-medium">Total Pinjaman Tersalurkan</span>: Jumlah pinjaman yang dicairkan dalam periode yang dipilih</li>
                        <li>• <span class="font-medium">Total Pokok Terbayar</span>: Jumlah pokok pinjaman yang sudah dibayar dalam periode yang dipilih</li>
                        <li>• <span class="font-medium">Sisa Pinjaman Aktif</span>: Total sisa pinjaman yang masih berstatus aktif (semua periode)</li>
                        <li>• <span class="font-medium">Total Pendapatan</span>: Jumlah pendapatan jasa dan denda yang diterima dalam periode yang dipilih</li>
                        <li>• <span class="font-medium">SHU</span>: Sisa Hasil Usaha dihitung berdasarkan persentase dari total pendapatan</li>
                        <li>• Semua data dihitung secara otomatis dari transaksi, bersifat <span class="font-semibold">READ-ONLY</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
