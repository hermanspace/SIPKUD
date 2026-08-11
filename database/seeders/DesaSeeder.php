<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Kecamatan;
use Illuminate\Database\Seeder;

class DesaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kecamatan = Kecamatan::all();

        if ($kecamatan->isEmpty()) {
            $this->command->warn('Kecamatan tidak ditemukan. Jalankan KecamatanSeeder terlebih dahulu.');

            return;
        }

        // Mapping kecamatan by kode
        $kecamatanMap = [];
        foreach ($kecamatan as $kec) {
            $kecamatanMap[$kec->kode_kecamatan] = $kec->id;
        }

        // Data desa berdasarkan kecamatan di Kabupaten Bengkalis, Riau
        $desaData = [
            // Kecamatan Bengkalis (KEC001)
            'KEC001' => [
                ['nama_desa' => 'Kelurahan Bengkalis Kota', 'kode_desa' => 'DES001'],
                ['nama_desa' => 'Kelurahan Damon', 'kode_desa' => 'DES002'],
                ['nama_desa' => 'Kelurahan Rimba Sekampung', 'kode_desa' => 'DES003'],
                ['nama_desa' => 'Desa Air Putih', 'kode_desa' => 'DES004'],
                ['nama_desa' => 'Desa Kelapapati', 'kode_desa' => 'DES005'],
                ['nama_desa' => 'Desa Kelemantan', 'kode_desa' => 'DES006'],
                ['nama_desa' => 'Desa Ketam Putih', 'kode_desa' => 'DES007'],
                ['nama_desa' => 'Desa Meskom', 'kode_desa' => 'DES008'],
                ['nama_desa' => 'Desa Pangkalan Batang', 'kode_desa' => 'DES009'],
                ['nama_desa' => 'Desa Pedekik', 'kode_desa' => 'DES010'],
                ['nama_desa' => 'Desa Pematang Duku', 'kode_desa' => 'DES011'],
                ['nama_desa' => 'Desa Penampi', 'kode_desa' => 'DES012'],
                ['nama_desa' => 'Desa Penebal', 'kode_desa' => 'DES013'],
                ['nama_desa' => 'Desa Prapat Tunggal', 'kode_desa' => 'DES014'],
                ['nama_desa' => 'Desa Sebauk', 'kode_desa' => 'DES015'],
                ['nama_desa' => 'Desa Sungai Alam', 'kode_desa' => 'DES016'],
                ['nama_desa' => 'Desa Sekodi', 'kode_desa' => 'DES017'],
                ['nama_desa' => 'Desa Senggoro', 'kode_desa' => 'DES018'],
                ['nama_desa' => 'Desa Teluk Latak', 'kode_desa' => 'DES019'],
                ['nama_desa' => 'Desa Temeran', 'kode_desa' => 'DES020'],
                ['nama_desa' => 'Desa Wonosari', 'kode_desa' => 'DES021'],
            ],
            // Kecamatan Bantan (KEC002)
            'KEC002' => [
                ['nama_desa' => 'Desa Bantan Air', 'kode_desa' => 'DES022'],
                ['nama_desa' => 'Desa Bantan Sari', 'kode_desa' => 'DES023'],
                ['nama_desa' => 'Desa Bantan Tengah', 'kode_desa' => 'DES024'],
                ['nama_desa' => 'Desa Bantan Timur', 'kode_desa' => 'DES025'],
                ['nama_desa' => 'Desa Bantan Tua', 'kode_desa' => 'DES026'],
                ['nama_desa' => 'Desa Berancah', 'kode_desa' => 'DES027'],
                ['nama_desa' => 'Desa Deluk', 'kode_desa' => 'DES028'],
                ['nama_desa' => 'Desa Jangkang', 'kode_desa' => 'DES029'],
                ['nama_desa' => 'Desa Kembung Baru', 'kode_desa' => 'DES030'],
                ['nama_desa' => 'Desa Kembung Luar', 'kode_desa' => 'DES031'],
                ['nama_desa' => 'Desa Mentayan', 'kode_desa' => 'DES032'],
                ['nama_desa' => 'Desa Muntai', 'kode_desa' => 'DES033'],
                ['nama_desa' => 'Desa Muntai Barat', 'kode_desa' => 'DES034'],
                ['nama_desa' => 'Desa Pambang Baru', 'kode_desa' => 'DES035'],
                ['nama_desa' => 'Desa Pambang Pesisir', 'kode_desa' => 'DES036'],
                ['nama_desa' => 'Desa Pasiran', 'kode_desa' => 'DES037'],
                ['nama_desa' => 'Desa Resam Lapis', 'kode_desa' => 'DES038'],
                ['nama_desa' => 'Desa Selat Baru', 'kode_desa' => 'DES039'],
                ['nama_desa' => 'Desa Suka Maju', 'kode_desa' => 'DES040'],
                ['nama_desa' => 'Desa Teluk Lancar', 'kode_desa' => 'DES041'],
                ['nama_desa' => 'Desa Teluk Pambang', 'kode_desa' => 'DES042'],
                ['nama_desa' => 'Desa Teluk Papal', 'kode_desa' => 'DES043'],
                ['nama_desa' => 'Desa Ulu Pulau', 'kode_desa' => 'DES044'],
            ],
            // Kecamatan Bukit Batu (KEC003)
            'KEC003' => [
                ['nama_desa' => 'Kelurahan Sungai Pakning', 'kode_desa' => 'DES045'],
                ['nama_desa' => 'Desa Batang Duku', 'kode_desa' => 'DES046'],
                ['nama_desa' => 'Desa Bukit Batu', 'kode_desa' => 'DES047'],
                ['nama_desa' => 'Desa Buruk Bakul', 'kode_desa' => 'DES048'],
                ['nama_desa' => 'Desa Dompas', 'kode_desa' => 'DES049'],
                ['nama_desa' => 'Desa Pangkalan Jambi', 'kode_desa' => 'DES050'],
                ['nama_desa' => 'Desa Pakning Asal', 'kode_desa' => 'DES051'],
                ['nama_desa' => 'Desa Sejangat', 'kode_desa' => 'DES052'],
                ['nama_desa' => 'Desa Sukajadi', 'kode_desa' => 'DES053'],
                ['nama_desa' => 'Desa Sungai Selari', 'kode_desa' => 'DES054'],
            ],
            // Kecamatan Mandau (KEC004)
            'KEC004' => [
                ['nama_desa' => 'Kelurahan Air Jamban', 'kode_desa' => 'DES055'],
                ['nama_desa' => 'Kelurahan Babussalam', 'kode_desa' => 'DES056'],
                ['nama_desa' => 'Kelurahan Balik Alam', 'kode_desa' => 'DES057'],
                ['nama_desa' => 'Kelurahan Batang Serosa', 'kode_desa' => 'DES058'],
                ['nama_desa' => 'Kelurahan Duri Barat', 'kode_desa' => 'DES059'],
                ['nama_desa' => 'Kelurahan Duri Timur', 'kode_desa' => 'DES060'],
                ['nama_desa' => 'Kelurahan Gajah Sakti', 'kode_desa' => 'DES061'],
                ['nama_desa' => 'Kelurahan Pematang Pudu', 'kode_desa' => 'DES062'],
                ['nama_desa' => 'Kelurahan Talang Mandi', 'kode_desa' => 'DES063'],
                ['nama_desa' => 'Desa Bathin Betuah', 'kode_desa' => 'DES064'],
                ['nama_desa' => 'Desa Harapan Baru', 'kode_desa' => 'DES065'],
            ],
            // Kecamatan Rupat (KEC005)
            'KEC005' => [
                ['nama_desa' => 'Kelurahan Batu Panjang', 'kode_desa' => 'DES066'],
                ['nama_desa' => 'Kelurahan Pergam', 'kode_desa' => 'DES067'],
                ['nama_desa' => 'Kelurahan Tanjung Kapal', 'kode_desa' => 'DES068'],
                ['nama_desa' => 'Kelurahan Terkul', 'kode_desa' => 'DES069'],
                ['nama_desa' => 'Desa Darul Aman', 'kode_desa' => 'DES070'],
                ['nama_desa' => 'Desa Hutan Panjang', 'kode_desa' => 'DES071'],
                ['nama_desa' => 'Desa Makeruh', 'kode_desa' => 'DES072'],
                ['nama_desa' => 'Desa Pangkalan Nyirih', 'kode_desa' => 'DES073'],
                ['nama_desa' => 'Desa Parit Kebumen', 'kode_desa' => 'DES074'],
                ['nama_desa' => 'Desa Sukarjo Mesin', 'kode_desa' => 'DES075'],
                ['nama_desa' => 'Desa Sungai Cingam', 'kode_desa' => 'DES076'],
                ['nama_desa' => 'Desa Teluk Lecah', 'kode_desa' => 'DES077'],
                ['nama_desa' => 'Desa Sri Tanjung', 'kode_desa' => 'DES078'],
                ['nama_desa' => 'Desa Dungun Baru', 'kode_desa' => 'DES079'],
                ['nama_desa' => 'Desa Pancur Jaya', 'kode_desa' => 'DES080'],
                ['nama_desa' => 'Desa Pangkalan Pinang', 'kode_desa' => 'DES081'],
            ],
            // Kecamatan Rupat Utara (KEC006)
            'KEC006' => [
                ['nama_desa' => 'Desa Tanjung Medang', 'kode_desa' => 'DES082'],
                ['nama_desa' => 'Desa Teluk Rhu', 'kode_desa' => 'DES083'],
                ['nama_desa' => 'Desa Tanjung Punak', 'kode_desa' => 'DES084'],
                ['nama_desa' => 'Desa Puteri Sembilan', 'kode_desa' => 'DES085'],
                ['nama_desa' => 'Desa Kadur', 'kode_desa' => 'DES086'],
                ['nama_desa' => 'Desa Titi Akar', 'kode_desa' => 'DES087'],
                ['nama_desa' => 'Desa Hutan Ayu', 'kode_desa' => 'DES088'],
                ['nama_desa' => 'Desa Suka Damai', 'kode_desa' => 'DES089'],
            ],
            // Kecamatan Siak Kecil (KEC007)
            'KEC007' => [
                ['nama_desa' => 'Desa Bandar Jaya', 'kode_desa' => 'DES090'],
                ['nama_desa' => 'Desa Koto Raja', 'kode_desa' => 'DES091'],
                ['nama_desa' => 'Desa Langkat', 'kode_desa' => 'DES092'],
                ['nama_desa' => 'Desa Liang Banir', 'kode_desa' => 'DES093'],
                ['nama_desa' => 'Desa Lubuk Garam', 'kode_desa' => 'DES094'],
                ['nama_desa' => 'Desa Lubuk Gaung', 'kode_desa' => 'DES095'],
                ['nama_desa' => 'Desa Lubuk Muda', 'kode_desa' => 'DES096'],
                ['nama_desa' => 'Desa Muara Dua', 'kode_desa' => 'DES097'],
                ['nama_desa' => 'Desa Sadar Jaya', 'kode_desa' => 'DES098'],
                ['nama_desa' => 'Desa Sepotong', 'kode_desa' => 'DES099'],
                ['nama_desa' => 'Desa Sumber Jaya', 'kode_desa' => 'DES100'],
                ['nama_desa' => 'Desa Sungai Nibung', 'kode_desa' => 'DES101'],
                ['nama_desa' => 'Desa Sungai Limau', 'kode_desa' => 'DES102'],
                ['nama_desa' => 'Desa Sungai Siput', 'kode_desa' => 'DES103'],
                ['nama_desa' => 'Desa Tanjung Belit', 'kode_desa' => 'DES104'],
                ['nama_desa' => 'Desa Tanjung Damai', 'kode_desa' => 'DES105'],
                ['nama_desa' => 'Desa Tanjung Datuk', 'kode_desa' => 'DES106'],
            ],
            // Kecamatan Pinggir (KEC008)
            'KEC008' => [
                ['nama_desa' => 'Kelurahan Balai Raja', 'kode_desa' => 'DES107'],
                ['nama_desa' => 'Kelurahan Titian Antui', 'kode_desa' => 'DES108'],
                ['nama_desa' => 'Desa Balai Pungut', 'kode_desa' => 'DES109'],
                ['nama_desa' => 'Desa Buluh Apo', 'kode_desa' => 'DES110'],
                ['nama_desa' => 'Desa Muara Basung', 'kode_desa' => 'DES111'],
                ['nama_desa' => 'Desa Pangkalan Libut', 'kode_desa' => 'DES112'],
                ['nama_desa' => 'Desa Pinggir', 'kode_desa' => 'DES113'],
                ['nama_desa' => 'Desa Semunai', 'kode_desa' => 'DES114'],
                ['nama_desa' => 'Desa Sungai Meranti', 'kode_desa' => 'DES115'],
                ['nama_desa' => 'Desa Tengganau', 'kode_desa' => 'DES116'],
            ],
            // Kecamatan Bandar Laksamana (KEC009)
            'KEC009' => [
                ['nama_desa' => 'Desa Api-Api', 'kode_desa' => 'DES117'],
                ['nama_desa' => 'Desa Bukit Kerikil', 'kode_desa' => 'DES118'],
                ['nama_desa' => 'Desa Parit Satu Api-Api', 'kode_desa' => 'DES119'],
                ['nama_desa' => 'Desa Sepahat', 'kode_desa' => 'DES120'],
                ['nama_desa' => 'Desa Tanjung Leban', 'kode_desa' => 'DES121'],
                ['nama_desa' => 'Desa Temiang', 'kode_desa' => 'DES122'],
                ['nama_desa' => 'Desa Tenggayun', 'kode_desa' => 'DES123'],
            ],
            // Kecamatan Talang Muandau (KEC010)
            'KEC010' => [
                ['nama_desa' => 'Desa Beringin', 'kode_desa' => 'DES124'],
                ['nama_desa' => 'Desa Koto Pait Beringin', 'kode_desa' => 'DES125'],
                ['nama_desa' => 'Desa Kuala Penaso', 'kode_desa' => 'DES126'],
                ['nama_desa' => 'Desa Melibur', 'kode_desa' => 'DES127'],
                ['nama_desa' => 'Desa Serai Wangi', 'kode_desa' => 'DES128'],
                ['nama_desa' => 'Desa Tasik Serai', 'kode_desa' => 'DES129'],
                ['nama_desa' => 'Desa Tasik Serai Barat', 'kode_desa' => 'DES130'],
                ['nama_desa' => 'Desa Tasik Serai Timur', 'kode_desa' => 'DES131'],
                ['nama_desa' => 'Desa Tasik Tebing Serai', 'kode_desa' => 'DES132'],
            ],
            // Kecamatan Bathin Solapan (KEC011)
            'KEC011' => [
                ['nama_desa' => 'Desa Air Kulim', 'kode_desa' => 'DES133'],
                ['nama_desa' => 'Desa Balai Makam', 'kode_desa' => 'DES134'],
                ['nama_desa' => 'Desa Bathin Sobanga', 'kode_desa' => 'DES135'],
                ['nama_desa' => 'Desa Boncah Mahang', 'kode_desa' => 'DES136'],
                ['nama_desa' => 'Desa Buluh Manis', 'kode_desa' => 'DES137'],
                ['nama_desa' => 'Desa Bumbung', 'kode_desa' => 'DES138'],
                ['nama_desa' => 'Desa Kesumbo Ampai', 'kode_desa' => 'DES139'],
                ['nama_desa' => 'Desa Pamesi', 'kode_desa' => 'DES140'],
                ['nama_desa' => 'Desa Pematang Obo', 'kode_desa' => 'DES141'],
                ['nama_desa' => 'Desa Petani', 'kode_desa' => 'DES142'],
                ['nama_desa' => 'Desa Sebangar', 'kode_desa' => 'DES143'],
                ['nama_desa' => 'Desa Simpang Padang', 'kode_desa' => 'DES144'],
                ['nama_desa' => 'Desa Tambusai Batang Dui', 'kode_desa' => 'DES145'],
            ],
        ];

        $desaCounter = 1;
        foreach ($desaData as $kodeKecamatan => $desaList) {
            if (! isset($kecamatanMap[$kodeKecamatan])) {
                continue;
            }

            $kecamatanId = $kecamatanMap[$kodeKecamatan];

            foreach ($desaList as $desa) {
                Desa::firstOrCreate(
                    ['kode_desa' => $desa['kode_desa']],
                    [
                        'kecamatan_id' => $kecamatanId,
                        'nama_desa' => $desa['nama_desa'],
                        'kode_desa' => $desa['kode_desa'],
                        'status' => 'aktif',
                    ]
                );
                $desaCounter++;
            }
        }

        $this->command->info("Seeder desa selesai. Total: {$desaCounter} desa/kelurahan.");
    }
}
