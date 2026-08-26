<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl">Impor Data Historis</flux:heading>
        <flux:heading size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
            Muat riwayat pemanfaat dan pinjaman dari file Excel UEK-SP lama (sheet LPP-UEK)
        </flux:heading>
    </div>

    @if($pesanError)
        <div class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 rounded-lg text-sm">
            {{ $pesanError }}
        </div>
    @endif

    {{-- Langkah 1: Unggah --}}
    @if($step === 1)
        <flux:card class="space-y-4">
            <flux:heading size="lg">1. Unggah File Excel</flux:heading>
            <div class="text-sm text-zinc-600 dark:text-zinc-400 space-y-1">
                <p>Gunakan <b>file laporan bulan terakhir</b> desa Anda (format .xls template kabupaten). Sheet LPP-UEK di dalamnya sudah memuat seluruh riwayat kumulatif, jadi cukup satu file.</p>
                <p>Yang akan diimpor: data anggota (dengan NIK sementara), seluruh pinjaman (aktif dan lunas), riwayat angsuran, serta satu jurnal memorial saldo awal piutang. Kas tidak tersentuh.</p>
            </div>
            <div>
                <input type="file" wire:model="uploadFile" accept=".xls,.xlsx"
                    class="block w-full text-sm text-zinc-600 dark:text-zinc-300 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-indigo-700" />
                <flux:error name="uploadFile" />
                <div wire:loading wire:target="uploadFile" class="mt-2 text-sm text-indigo-600">Mengunggah dan membaca file…</div>
            </div>
        </flux:card>
    @endif

    {{-- Langkah 2: Tinjau --}}
    @if($step === 2)
        <flux:card class="space-y-4">
            <flux:heading size="lg">2. Tinjau Hasil Pembacaan @if($periode)<span class="text-zinc-500 font-normal">— periode {{ $periode }}</span>@endif</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="p-4 rounded-lg bg-indigo-50 dark:bg-indigo-900/30">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Pinjaman terbaca</p>
                    <p class="text-2xl font-bold">{{ number_format($ringkasan['jumlah_pinjaman'] ?? 0) }}</p>
                    <p class="text-xs text-zinc-500">{{ number_format($ringkasan['jumlah_aktif'] ?? 0) }} aktif · {{ number_format($ringkasan['jumlah_lunas'] ?? 0) }} lunas</p>
                </div>
                <div class="p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/30">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Anggota unik</p>
                    <p class="text-2xl font-bold">{{ number_format($ringkasan['jumlah_anggota'] ?? 0) }}</p>
                    <p class="text-xs text-zinc-500">NIK sementara, dilengkapi belakangan</p>
                </div>
                <div class="p-4 rounded-lg bg-sky-50 dark:bg-sky-900/30">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Total pokok tersalurkan</p>
                    <p class="text-xl font-bold">Rp {{ number_format($ringkasan['total_pokok'] ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-900/30">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Sisa piutang (akan dijurnal)</p>
                    <p class="text-xl font-bold">Rp {{ number_format($ringkasan['total_sisa_pokok'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>

            {{-- Kontrol silang LPP vs LKN I --}}
            @if(($kontrol['cocok'] ?? null) === true)
                <div class="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-sm text-emerald-800 dark:text-emerald-200">
                    ✔ Pemeriksaan silang <b>COCOK</b>: total sisa pinjaman di LPP-UEK sama dengan saldo akun Piutang di Neraca Percobaan (LKN I) — Rp {{ number_format($kontrol['saldo_piutang_lkn'], 0, ',', '.') }}. File sehat.
                </div>
            @elseif(($kontrol['cocok'] ?? null) === false)
                <div class="p-3 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-sm text-red-800 dark:text-red-200">
                    ✘ Pemeriksaan silang <b>TIDAK COCOK</b>: sisa pinjaman LPP = Rp {{ number_format($kontrol['sisa_lpp'], 0, ',', '.') }}, saldo Piutang LKN I = Rp {{ number_format($kontrol['saldo_piutang_lkn'], 0, ',', '.') }}
                    (selisih Rp {{ number_format(abs($kontrol['sisa_lpp'] - $kontrol['saldo_piutang_lkn']), 0, ',', '.') }}).
                    File Excel Anda kemungkinan mengandung salah salin saldo. Impor tetap bisa dilanjutkan (memakai angka LPP), tetapi sebaiknya periksa dulu file-nya.
                </div>
            @else
                <div class="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-sm text-zinc-600 dark:text-zinc-300">
                    Sheet LKN I tidak ditemukan — pemeriksaan silang dilewati.
                </div>
            @endif

            @if(count($galat))
                <div class="p-3 rounded-lg bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 text-sm">
                    <p class="font-semibold text-amber-800 dark:text-amber-200 mb-1">{{ count($galat) }} baris dilewati:</p>
                    <ul class="list-disc ml-5 text-amber-700 dark:text-amber-300 max-h-40 overflow-y-auto">
                        @foreach($galat as $g)<li>{{ $g }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <flux:separator />

            <flux:heading size="sm">Pengaturan pembukuan saldo awal</flux:heading>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <flux:select wire:model="unit_usaha_id" label="Unit Usaha">
                        @foreach($units as $u)<option value="{{ $u->id }}">{{ $u->nama_unit }}</option>@endforeach
                    </flux:select>
                    <flux:error name="unit_usaha_id" />
                </div>
                <div>
                    <flux:select wire:model="akun_piutang_id" label="Akun Piutang (Debit)">
                        @foreach($akunAset as $a)<option value="{{ $a->id }}">{{ $a->kode_akun }} - {{ $a->nama_akun }}</option>@endforeach
                    </flux:select>
                    <flux:error name="akun_piutang_id" />
                </div>
                <div>
                    <flux:select wire:model="akun_modal_id" label="Akun Modal (Kredit)">
                        @foreach($akunEkuitas as $a)<option value="{{ $a->id }}">{{ $a->kode_akun }} - {{ $a->nama_akun }}</option>@endforeach
                    </flux:select>
                    <flux:error name="akun_modal_id" />
                </div>
            </div>

            <div class="flex gap-3">
                <flux:button variant="primary" wire:click="jalankan" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="jalankan">Tulis ke SIPKUD</span>
                    <span wire:loading wire:target="jalankan">Memproses… mohon tunggu</span>
                </flux:button>
                <flux:button variant="ghost" wire:click="batal">Batal</flux:button>
            </div>
            <p class="text-xs text-zinc-500">Aman diulang: pinjaman dengan No SPPK yang sudah pernah diimpor akan dilewati otomatis.</p>
        </flux:card>
    @endif

    {{-- Langkah 3: Hasil --}}
    @if($step === 3)
        <flux:card class="space-y-4">
            <flux:heading size="lg">3. Impor Selesai</flux:heading>
            <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <tbody>
                    <tr><td class="px-4 py-2">Anggota baru dibuat</td><td class="px-4 py-2 text-right font-semibold">{{ number_format($hasil['anggota_baru'] ?? 0) }}</td></tr>
                    <tr><td class="px-4 py-2">Terhubung ke anggota yang sudah ada</td><td class="px-4 py-2 text-right font-semibold">{{ number_format($hasil['anggota_terhubung'] ?? 0) }}</td></tr>
                    <tr><td class="px-4 py-2">Pinjaman diimpor</td><td class="px-4 py-2 text-right font-semibold">{{ number_format($hasil['pinjaman_baru'] ?? 0) }}</td></tr>
                    <tr><td class="px-4 py-2">Pinjaman dilewati (sudah pernah diimpor)</td><td class="px-4 py-2 text-right font-semibold">{{ number_format($hasil['pinjaman_dilewati'] ?? 0) }}</td></tr>
                    <tr><td class="px-4 py-2">Baris riwayat angsuran dibuat</td><td class="px-4 py-2 text-right font-semibold">{{ number_format($hasil['angsuran_dibuat'] ?? 0) }}</td></tr>
                    <tr class="border-t border-zinc-200 dark:border-zinc-700">
                        <td class="px-4 py-2">Saldo awal piutang dijurnal @if(!empty($hasil['nomor_jurnal']))<span class="text-xs text-zinc-500">({{ $hasil['nomor_jurnal'] }})</span>@endif</td>
                        <td class="px-4 py-2 text-right font-semibold">Rp {{ number_format($hasil['sisa_pokok_dijurnal'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
            </div>
            <div class="p-3 rounded-lg bg-sky-50 dark:bg-sky-900/30 border border-sky-200 dark:border-sky-800 text-sm text-sky-800 dark:text-sky-200">
                <b>Langkah selanjutnya:</b> lengkapi NIK anggota hasil impor secara bertahap di menu Anggota (bertanda "NIK sementara") — pinjaman baru untuk anggota tersebut terbuka setelah NIK diisi. Periksa juga Laporan Kolektibilitas dan Neraca Saldo Anda.
            </div>
            <div class="flex gap-3">
                <flux:button :href="route('anggota.index')" wire:navigate variant="primary">Buka Daftar Anggota</flux:button>
                <flux:button :href="route('laporan.kolektibilitas')" wire:navigate variant="ghost">Lihat Kolektibilitas</flux:button>
                <flux:button wire:click="batal" variant="ghost">Impor File Lain</flux:button>
            </div>
        </flux:card>
    @endif
</div>
