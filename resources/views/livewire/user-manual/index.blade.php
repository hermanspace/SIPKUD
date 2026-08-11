<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">User Manual</h1>
        <p class="text-zinc-600 dark:text-zinc-400">Panduan lengkap penggunaan sistem SIPKUD (Sistem Informasi Pengelolaan Keuangan Desa)</p>
    </div>

    <!-- Table of Contents -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
        <h2 class="text-2xl font-semibold text-zinc-900 dark:text-white mb-4">Daftar Isi</h2>
        <ul class="space-y-2 text-zinc-700 dark:text-zinc-300">
            <li><a href="#overview" class="text-blue-600 dark:text-blue-400 hover:underline" @click="activeSection = 'overview'">1. Gambaran Umum Sistem</a></li>
            <li><a href="#features" class="text-blue-600 dark:text-blue-400 hover:underline" @click="activeSection = 'features'">2. Fitur-Fitur Sistem</a></li>
            <li><a href="#workflow" class="text-blue-600 dark:text-blue-400 hover:underline" @click="activeSection = 'workflow'">3. Alur Kerja</a></li>
            <li><a href="#roles" class="text-blue-600 dark:text-blue-400 hover:underline" @click="activeSection = 'roles'">4. Peran dan Hak Akses</a></li>
            <li><a href="#master-data" class="text-blue-600 dark:text-blue-400 hover:underline" @click="activeSection = 'master-data'">5. Master Data</a></li>
            <li><a href="#transaksi" class="text-blue-600 dark:text-blue-400 hover:underline" @click="activeSection = 'transaksi'">6. Transaksi</a></li>
            <li><a href="#akuntansi" class="text-blue-600 dark:text-blue-400 hover:underline" @click="activeSection = 'akuntansi'">7. Akuntansi</a></li>
            <li><a href="#laporan" class="text-blue-600 dark:text-blue-400 hover:underline" @click="activeSection = 'laporan'">8. Laporan</a></li>
            <li><a href="#erd" class="text-blue-600 dark:text-blue-400 hover:underline" @click="activeSection = 'erd'">9. Entity Relationship Diagram (ERD)</a></li>
            <li><a href="#tips" class="text-blue-600 dark:text-blue-400 hover:underline" @click="activeSection = 'tips'">10. Tips & Best Practices</a></li>
        </ul>
    </div>

    <!-- 1. Gambaran Umum Sistem -->
    <div id="overview" class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
        <h2 class="text-2xl font-semibold text-zinc-900 dark:text-white mb-4">1. Gambaran Umum Sistem</h2>
        <div class="space-y-4 text-zinc-700 dark:text-zinc-300">
            <p><strong>SIPKUD</strong> adalah Sistem Informasi Pengelolaan Keuangan Desa yang dirancang khusus untuk mengelola keuangan BUM Desa (Badan Usaha Milik Desa) dengan pendekatan akuntansi double entry.</p>
            
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                <h3 class="font-semibold text-blue-900 dark:text-blue-300 mb-2">Prinsip Utama:</h3>
                <ul class="list-disc list-inside space-y-1">
                    <li><strong>Double Entry Accounting:</strong> Setiap transaksi harus balance (Debit = Kredit)</li>
                    <li><strong>Cash Basis + Memorial:</strong> Basis kas dengan jurnal memorial untuk transaksi non-kas</li>
                    <li><strong>Periode Bulanan:</strong> Laporan keuangan berdasarkan periode bulanan (YYYY-MM)</li>
                    <li><strong>Multi Unit Usaha:</strong> Support untuk beberapa unit usaha dalam satu desa</li>
                </ul>
            </div>

            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                <h3 class="font-semibold text-green-900 dark:text-green-300 mb-2">Dua Titik Input Utama:</h3>
                <ol class="list-decimal list-inside space-y-1">
                    <li><strong>Kas Harian:</strong> Untuk transaksi kas (masuk/keluar)</li>
                    <li><strong>Buku Memorial:</strong> Untuk transaksi non-kas (penyusutan, transfer, dll)</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- 2. Fitur-Fitur Sistem -->
    <div id="features" class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
        <h2 class="text-2xl font-semibold text-zinc-900 dark:text-white mb-4">2. Fitur-Fitur Sistem</h2>
        <div class="space-y-4">
            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Master Data</h3>
                <ul class="list-disc list-inside space-y-1 text-zinc-700 dark:text-zinc-300">
                    <li><strong>Kecamatan & Desa:</strong> Pengelolaan data wilayah (Super Admin)</li>
                    <li><strong>Kelompok:</strong> Pengelompokan anggota</li>
                    <li><strong>Anggota:</strong> Data anggota BUM Desa</li>
                    <li><strong>Akun (COA):</strong> Chart of Accounts untuk akuntansi</li>
                    <li><strong>Unit Usaha:</strong> Pengelolaan unit usaha (USP, UMUM, dll)</li>
                </ul>
            </div>

            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Transaksi</h3>
                <ul class="list-disc list-inside space-y-1 text-zinc-700 dark:text-zinc-300">
                    <li><strong>Pinjaman:</strong> Pencatatan pinjaman anggota (auto-create jurnal)</li>
                    <li><strong>Angsuran:</strong> Pembayaran angsuran pinjaman (auto-create jurnal multi-account)</li>
                </ul>
            </div>

            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Akuntansi</h3>
                <ul class="list-disc list-inside space-y-1 text-zinc-700 dark:text-zinc-300">
                    <li><strong>Kas Harian:</strong> Transaksi kas masuk/keluar dengan auto-create jurnal</li>
                    <li><strong>Buku Memorial:</strong> Transaksi non-kas (penyusutan, transfer, dll)</li>
                    <li><strong>Manajemen Periode:</strong> Posting dan closing periode akuntansi</li>
                </ul>
            </div>

            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Laporan</h3>
                <ul class="list-disc list-inside space-y-1 text-zinc-700 dark:text-zinc-300">
                    <li><strong>LPP UED:</strong> Laporan Pertanggungjawaban UED-SP</li>
                    <li><strong>Buku Kas:</strong> Laporan kas harian</li>
                    <li><strong>Laporan Akhir USP:</strong> Laporan akhir unit simpan pinjam</li>
                    <li><strong>Neraca Saldo:</strong> Trial balance dengan saldo awal, mutasi, saldo akhir</li>
                    <li><strong>Laba Rugi:</strong> Income statement (bulanan & kumulatif)</li>
                    <li><strong>Neraca:</strong> Balance sheet dengan perubahan modal</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 3. Alur Kerja -->
    <div id="workflow" class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
        <h2 class="text-2xl font-semibold text-zinc-900 dark:text-white mb-4">3. Alur Kerja</h2>
        <div class="space-y-6">
            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-3">Alur Transaksi Kas</h3>
                <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
                    <ol class="list-decimal list-inside space-y-2 text-zinc-700 dark:text-zinc-300">
                        <li>Input transaksi kas di menu <strong>Kas Harian</strong></li>
                        <li>Pilih akun kas dan akun lawan (pendapatan/beban)</li>
                        <li>Sistem otomatis membuat jurnal (Debit Kas, Kredit Akun Lawan atau sebaliknya)</li>
                        <li>Jurnal otomatis ter-post ke neraca saldo</li>
                        <li>Laporan keuangan otomatis ter-update</li>
                    </ol>
                </div>
            </div>

            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-3">Alur Transaksi Memorial</h3>
                <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
                    <ol class="list-decimal list-inside space-y-2 text-zinc-700 dark:text-zinc-300">
                        <li>Input transaksi memorial di menu <strong>Buku Memorial</strong></li>
                        <li>Input detail jurnal (minimal 2 akun, Debit = Kredit)</li>
                        <li>Sistem validasi balance (Debit harus = Kredit)</li>
                        <li>Jurnal otomatis ter-post ke neraca saldo</li>
                        <li>Laporan keuangan otomatis ter-update</li>
                    </ol>
                </div>
            </div>

            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-3">Alur Pinjaman & Angsuran</h3>
                <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
                    <ol class="list-decimal list-inside space-y-2 text-zinc-700 dark:text-zinc-300">
                        <li>Input pinjaman di menu <strong>Pinjaman</strong></li>
                        <li>Sistem otomatis membuat TransaksiKas keluar dan Jurnal (Debit Piutang, Kredit Kas)</li>
                        <li>Input angsuran di menu <strong>Angsuran</strong></li>
                        <li>Sistem otomatis membuat TransaksiKas masuk dan Jurnal multi-account (Debit Kas, Kredit Piutang + Pendapatan Jasa)</li>
                        <li>Status pinjaman otomatis ter-update</li>
                    </ol>
                </div>
            </div>

            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-3">Alur Closing Periode</h3>
                <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
                    <ol class="list-decimal list-inside space-y-2 text-zinc-700 dark:text-zinc-300">
                        <li>Post semua transaksi ke neraca saldo di menu <strong>Manajemen Periode</strong></li>
                        <li>Review neraca saldo dan laporan keuangan</li>
                        <li>Close periode (setelah close, transaksi tidak bisa diubah)</li>
                        <li>Saldo akhir periode ini menjadi saldo awal periode berikutnya</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Peran dan Hak Akses -->
    <div id="roles" class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
        <h2 class="text-2xl font-semibold text-zinc-900 dark:text-white mb-4">4. Peran dan Hak Akses</h2>
        <div class="space-y-4">
            <div class="border-l-4 border-red-500 pl-4">
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Super Admin</h3>
                <ul class="list-disc list-inside space-y-1 text-zinc-700 dark:text-zinc-300">
                    <li>Akses penuh ke semua desa dan kecamatan</li>
                    <li>Mengelola Kecamatan dan Desa</li>
                    <li>Mengelola Pengguna (Admin Kecamatan & Admin Desa)</li>
                    <li>Melihat semua laporan dengan filter desa</li>
                    <li><strong>READ ONLY</strong> untuk transaksi (tidak bisa create/edit/delete)</li>
                </ul>
            </div>

            <div class="border-l-4 border-blue-500 pl-4">
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Admin Kecamatan</h3>
                <ul class="list-disc list-inside space-y-1 text-zinc-700 dark:text-zinc-300">
                    <li>Akses ke semua desa di kecamatannya</li>
                    <li>Mengelola Kelompok dan Anggota</li>
                    <li>Mengelola Pengguna (Admin Desa di kecamatannya)</li>
                    <li>Melihat semua laporan dengan filter desa</li>
                    <li><strong>READ ONLY</strong> untuk transaksi (tidak bisa create/edit/delete)</li>
                </ul>
            </div>

            <div class="border-l-4 border-green-500 pl-4">
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Admin Desa</h3>
                <ul class="list-disc list-inside space-y-1 text-zinc-700 dark:text-zinc-300">
                    <li>Akses penuh ke desanya sendiri</li>
                    <li>Mengelola semua master data (Kelompok, Anggota, Akun, Unit Usaha)</li>
                    <li>Mengelola transaksi (Pinjaman, Angsuran, Kas Harian, Buku Memorial)</li>
                    <li>Mengelola periode akuntansi (posting & closing)</li>
                    <li>Melihat semua laporan desanya</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 5. Master Data -->
    <div id="master-data" class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
        <h2 class="text-2xl font-semibold text-zinc-900 dark:text-white mb-4">5. Master Data</h2>
        <div class="space-y-4 text-zinc-700 dark:text-zinc-300">
            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Akun (COA)</h3>
                <p>Chart of Accounts adalah daftar akun yang digunakan dalam sistem akuntansi. Terdapat 5 jenis akun:</p>
                <ul class="list-disc list-inside space-y-1 mt-2">
                    <li><strong>ASET:</strong> Kas, Bank, Piutang, Aset Tetap, dll</li>
                    <li><strong>KEWAJIBAN:</strong> Hutang Usaha, Hutang Bank, dll</li>
                    <li><strong>EKUITAS:</strong> Modal, Laba Ditahan, dll</li>
                    <li><strong>PENDAPATAN:</strong> Pendapatan Jasa Pinjaman, Pendapatan Simpanan, dll</li>
                    <li><strong>BEBAN:</strong> Beban Operasional, Beban Gaji, Beban Penyusutan, dll</li>
                </ul>
            </div>

            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Unit Usaha</h3>
                <p>Unit usaha adalah divisi atau bagian dari BUM Desa yang memiliki aktivitas bisnis terpisah:</p>
                <ul class="list-disc list-inside space-y-1 mt-2">
                    <li><strong>USP (Unit Simpan Pinjam):</strong> Unit usaha simpan pinjam</li>
                    <li><strong>UMUM:</strong> Unit usaha umum BUM Desa</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 6. Transaksi -->
    <div id="transaksi" class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
        <h2 class="text-2xl font-semibold text-zinc-900 dark:text-white mb-4">6. Transaksi</h2>
        <div class="space-y-4 text-zinc-700 dark:text-zinc-300">
            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Pinjaman</h3>
                <p>Pencatatan pinjaman anggota akan otomatis:</p>
                <ul class="list-disc list-inside space-y-1 mt-2">
                    <li>Membuat TransaksiKas keluar</li>
                    <li>Membuat Jurnal: Debit Piutang Pinjaman Anggota, Kredit Kas</li>
                    <li>Ter-post ke neraca saldo</li>
                </ul>
            </div>

            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Angsuran</h3>
                <p>Pencatatan angsuran akan otomatis:</p>
                <ul class="list-disc list-inside space-y-1 mt-2">
                    <li>Membuat TransaksiKas masuk</li>
                    <li>Membuat Jurnal multi-account: Debit Kas, Kredit Piutang (pokok) + Pendapatan Jasa (jasa) + Pendapatan Denda (denda)</li>
                    <li>Update status pinjaman</li>
                    <li>Ter-post ke neraca saldo</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 7. Akuntansi -->
    <div id="akuntansi" class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
        <h2 class="text-2xl font-semibold text-zinc-900 dark:text-white mb-4">7. Akuntansi</h2>
        <div class="space-y-4 text-zinc-700 dark:text-zinc-300">
            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Kas Harian</h3>
                <p>Menu untuk mencatat transaksi kas masuk dan keluar. Setiap transaksi akan otomatis membuat jurnal dengan format:</p>
                <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg mt-2">
                    <p class="font-mono text-sm">
                        <strong>Kas Masuk:</strong><br>
                        Debit: Kas<br>
                        Kredit: Akun Lawan (Pendapatan/dll)<br><br>
                        <strong>Kas Keluar:</strong><br>
                        Debit: Akun Lawan (Beban/dll)<br>
                        Kredit: Kas
                    </p>
                </div>
            </div>

            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Buku Memorial</h3>
                <p>Menu untuk mencatat transaksi non-kas seperti:</p>
                <ul class="list-disc list-inside space-y-1 mt-2">
                    <li>Penyusutan aset</li>
                    <li>Bunga bank</li>
                    <li>Pajak bank</li>
                    <li>Transfer antar rekening</li>
                </ul>
                <p class="mt-2"><strong>Penting:</strong> Total Debit harus sama dengan Total Kredit!</p>
            </div>

            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Manajemen Periode</h3>
                <p>Menu untuk mengelola periode akuntansi:</p>
                <ul class="list-disc list-inside space-y-1 mt-2">
                    <li><strong>Posting:</strong> Post semua transaksi ke neraca saldo</li>
                    <li><strong>Closing:</strong> Tutup periode (setelah close, transaksi tidak bisa diubah)</li>
                    <li><strong>Review:</strong> Lihat detail neraca saldo per periode</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 8. Laporan -->
    <div id="laporan" class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
        <h2 class="text-2xl font-semibold text-zinc-900 dark:text-white mb-4">8. Laporan</h2>
        <div class="space-y-4 text-zinc-700 dark:text-zinc-300">
            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Neraca Saldo</h3>
                <p>Menampilkan saldo awal, mutasi bulan berjalan, dan saldo akhir untuk semua akun. Format:</p>
                <ul class="list-disc list-inside space-y-1 mt-2">
                    <li><strong>Saldo Awal:</strong> Saldo akhir periode sebelumnya</li>
                    <li><strong>Mutasi:</strong> Total debit dan kredit bulan berjalan</li>
                    <li><strong>Saldo Akhir:</strong> Saldo awal + Mutasi</li>
                </ul>
            </div>

            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Laba Rugi</h3>
                <p>Menampilkan pendapatan dan beban untuk menghitung laba/rugi. Tersedia 2 mode:</p>
                <ul class="list-disc list-inside space-y-1 mt-2">
                    <li><strong>Bulanan:</strong> Menggunakan mutasi bulan berjalan</li>
                    <li><strong>Kumulatif:</strong> Menggunakan saldo akhir (akumulasi dari awal tahun)</li>
                </ul>
            </div>

            <div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Neraca</h3>
                <p>Menampilkan posisi keuangan (Aset, Kewajiban, Modal) dan perubahan modal. Format:</p>
                <ul class="list-disc list-inside space-y-1 mt-2">
                    <li><strong>ASET:</strong> Total aset</li>
                    <li><strong>KEWAJIBAN:</strong> Total kewajiban</li>
                    <li><strong>MODAL:</strong> Total modal</li>
                    <li><strong>Validasi:</strong> ASET harus = KEWAJIBAN + MODAL</li>
                    <li><strong>Perubahan Modal:</strong> Modal Awal + Laba Bersih + Prive = Modal Akhir</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 9. ERD -->
    <div id="erd" class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
        <h2 class="text-2xl font-semibold text-zinc-900 dark:text-white mb-4">9. Entity Relationship Diagram (ERD)</h2>
        <div class="space-y-4 text-zinc-700 dark:text-zinc-300">
            <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-3">Struktur Database Utama</h3>
                <div class="space-y-3 font-mono text-sm">
                    <div>
                        <strong>Master Data:</strong>
                        <ul class="list-disc list-inside ml-4 mt-1">
                            <li>kecamatan → desa → kelompok → anggota</li>
                            <li>desa → akun (COA)</li>
                            <li>desa → unit_usaha</li>
                        </ul>
                    </div>
                    <div>
                        <strong>Transaksi:</strong>
                        <ul class="list-disc list-inside ml-4 mt-1">
                            <li>anggota → pinjaman → angsuran_pinjaman</li>
                            <li>pinjaman → transaksi_kas</li>
                            <li>angsuran_pinjaman → transaksi_kas</li>
                        </ul>
                    </div>
                    <div>
                        <strong>Akuntansi:</strong>
                        <ul class="list-disc list-inside ml-4 mt-1">
                            <li>transaksi_kas → jurnal → jurnal_detail</li>
                            <li>jurnal → neraca_saldo</li>
                            <li>akun → jurnal_detail</li>
                            <li>akun → neraca_saldo</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                <h3 class="font-semibold text-blue-900 dark:text-blue-300 mb-2">Alur Data Akuntansi:</h3>
                <div class="font-mono text-sm text-blue-800 dark:text-blue-200">
                    <p>TransaksiKas / Jurnal → JurnalDetail → NeracaSaldo → Laporan</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 10. Tips & Best Practices -->
    <div id="tips" class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
        <h2 class="text-2xl font-semibold text-zinc-900 dark:text-white mb-4">10. Tips & Best Practices</h2>
        <div class="space-y-4 text-zinc-700 dark:text-zinc-300">
            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                <h3 class="font-semibold text-yellow-900 dark:text-yellow-300 mb-2">⚠️ Kontrol Internal:</h3>
                <ul class="list-disc list-inside space-y-1">
                    <li><strong>Validasi Balance:</strong> Sistem otomatis memvalidasi Debit = Kredit (hard block)</li>
                    <li><strong>Periode Closed:</strong> Transaksi di periode yang sudah di-close tidak bisa diubah</li>
                    <li><strong>Audit Log:</strong> Semua perubahan tercatat dengan lengkap</li>
                    <li><strong>Soft Delete:</strong> Data yang dihapus tetap tersimpan dengan jejak histori</li>
                </ul>
            </div>

            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                <h3 class="font-semibold text-green-900 dark:text-green-300 mb-2">✅ Best Practices:</h3>
                <ul class="list-disc list-inside space-y-1">
                    <li>Input transaksi harian secara rutin</li>
                    <li>Review neraca saldo sebelum closing periode</li>
                    <li>Pastikan semua transaksi sudah di-post sebelum closing</li>
                    <li>Backup data secara berkala</li>
                    <li>Gunakan filter desa dan periode saat melihat laporan</li>
                </ul>
            </div>

            <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                <h3 class="font-semibold text-red-900 dark:text-red-300 mb-2">❌ Yang Harus Dihindari:</h3>
                <ul class="list-disc list-inside space-y-1">
                    <li>Jangan mengubah transaksi di periode yang sudah di-close</li>
                    <li>Jangan menghapus data tanpa alasan yang jelas</li>
                    <li>Jangan membuat jurnal yang tidak balance</li>
                    <li>Jangan skip validasi sistem</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-6 text-center text-zinc-600 dark:text-zinc-400">
        <p>© {{ date('Y') }} SIPKUD - Sistem Informasi Pengelolaan Keuangan Desa</p>
        <p class="mt-2 text-sm">Untuk pertanyaan lebih lanjut, hubungi administrator sistem.</p>
    </div>

    {{-- Flowchart alur proses akuntansi (semua level akses) --}}
    @include('partials.alur-akuntansi')

</div>
