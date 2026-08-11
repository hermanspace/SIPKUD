<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Akuntansi SIPKUD
|--------------------------------------------------------------------------
| Parameter alur akuntansi yang mengikuti standar pelaporan BUM Desa
| (PP 11/2021, Kepmendesa PDTT 136/2022) dan konvensi USP/KSP.
*/

return [

    // Laporan Arus Kas (metode langsung): akun lawan bertipe 'aset' dengan
    // prefix kode berikut diklasifikasikan sebagai aktivitas INVESTASI
    // (aset tetap). Aset lancar lain (piutang, perlengkapan) = OPERASI.
    'arus_kas' => [
        'investasi_prefixes' => ['1-13', '1-14'],
    ],

    // Tutup buku tahunan: nama akun tujuan jurnal penutup.
    'tutup_buku' => [
        'akun_shu_berjalan' => 'SHU Tahun Berjalan',
        'akun_shu_tahun_lalu' => 'SHU Tahun Lalu',
    ],

    // Alokasi SHU (pembagian laba bersih) sesuai AD/ART - total harus 100.
    // SHU = laba bersih (pendapatan - beban), BUKAN persentase pendapatan.
    'alokasi_shu' => [
        ['nama' => 'Cadangan Umum', 'persen' => 25],
        ['nama' => 'Jasa Anggota', 'persen' => 40],
        ['nama' => 'Pengurus & Pengelola', 'persen' => 15],
        ['nama' => 'Dana Sosial', 'persen' => 10],
        ['nama' => 'Pendapatan Asli Desa (PAD)', 'persen' => 10],
    ],

    // Kolektibilitas pinjaman: batas minimal TUNGGAKAN (dalam bulan angsuran)
    // untuk masuk kategori. Mengikuti konvensi penilaian kesehatan KSP/USP.
    'kolektibilitas' => [
        'kurang_lancar' => 1,  // 1-3 bulan tunggakan
        'diragukan' => 4,      // 4-6 bulan tunggakan
        'macet' => 7,          // >= 7 bulan tunggakan
    ],

    // Penyisihan piutang tak tertagih (% dari sisa pinjaman per kategori),
    // mengikuti konvensi PPAP KSP/USP.
    'penyisihan' => [
        'lancar' => 0.5,
        'kurang_lancar' => 10,
        'diragukan' => 50,
        'macet' => 100,
    ],

    // Nama akun yang dipakai jurnal penyisihan otomatis.
    'akun_penyisihan' => [
        'beban' => 'Beban Penyisihan Kerugian Piutang',
        'cadangan' => 'Cadangan Kerugian Piutang',
    ],
];
