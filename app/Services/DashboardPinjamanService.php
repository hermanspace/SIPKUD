<?php

namespace App\Services;

use App\Models\Pinjaman;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Menghitung statistik peminjaman untuk dashboard.
 * Scope pinjaman (desa/kecamatan/semua) harus sudah diterapkan pada query.
 */
class DashboardPinjamanService
{
    /**
     * @param  Builder<Pinjaman>|null  $pinjamanQuery  Base query pinjaman (sudah di-scope desa/kecamatan). Null = semua.
     * @return array<string, mixed>
     */
    public function getStatistik(?Builder $pinjamanQuery = null): array
    {
        $query = $pinjamanQuery ?? Pinjaman::query();

        $pinjamanIds = (clone $query)->pluck('id');
        $totalNominal = (clone $query)->sum('jumlah_pinjaman');
        $countPinjamanLunas = (clone $query)->where('status_pinjaman', 'lunas')->count();
        $countPinjamanAktif = (clone $query)->where('status_pinjaman', 'aktif')->count();
        // Peminjam = orang (distinct anggota)
        $peminjamLunas = (int) ((clone $query)->where('status_pinjaman', 'lunas')->selectRaw('count(distinct pinjaman.anggota_id) as c')->value('c') ?? 0);
        $peminjamBelumLunas = (int) ((clone $query)->where('status_pinjaman', 'aktif')->selectRaw('count(distinct pinjaman.anggota_id) as c')->value('c') ?? 0);

        // Jumlah peminjam (distinct anggota)
        $jumlahPeminjam = (int) ((clone $query)->selectRaw('count(distinct pinjaman.anggota_id) as c')->value('c') ?? 0);
        $anggotaIds = (clone $query)->distinct()->pluck('anggota_id');

        // Laki-laki / Perempuan (peminjam = punya pinjaman)
        $peminjamLaki = 0;
        $peminjamPerempuan = 0;
        if ($anggotaIds->isNotEmpty()) {
            $gender = DB::table('anggota')
                ->whereIn('id', $anggotaIds)
                ->selectRaw('jenis_kelamin, count(*) as cnt')
                ->groupBy('jenis_kelamin')
                ->pluck('cnt', 'jenis_kelamin');
            $peminjamLaki = (int) ($gender->get('L', 0) + $gender->get('l', 0));
            $peminjamPerempuan = (int) ($gender->get('P', 0) + $gender->get('p', 0));
        }

        // Total pokok sudah dikembalikan (dari angsuran)
        $totalPokokDibayar = DB::table('angsuran_pinjaman')
            ->whereIn('pinjaman_id', $pinjamanIds)
            ->sum('pokok_dibayar');

        $totalDibayar = DB::table('angsuran_pinjaman')
            ->whereIn('pinjaman_id', $pinjamanIds)
            ->sum('total_dibayar');

        // Persentase Pengembalian: (pokok sudah dikembalikan / total pinjaman disalurkan) * 100
        $persenPengembalian = $totalNominal > 0
            ? round(($totalPokokDibayar / $totalNominal) * 100, 0)
            : 0;

        // Tunggakan & jatuh tempo: untuk setiap pinjaman aktif, hitung angsuran yang telat
        $jumlahTunggakan = 0;
        $jumlahPenunggak = 0;
        $peminjamJatuhTempo = 0;
        $nilaiJatuhTempo = 0;

        $pinjamanAktifList = (clone $query)->with('angsuran')->where('status_pinjaman', 'aktif')->get();
        $today = Carbon::today();

        foreach ($pinjamanAktifList as $p) {
            $jangka = (int) $p->jangka_waktu_bulan;
            $pokokPerBulan = $jangka > 0 ? (float) $p->jumlah_pinjaman / $jangka : 0;
            $jasaPerBulan = $jangka > 0 ? (float) $p->jumlah_pinjaman * ((float) $p->jasa_persen / 100) : 0;
            $angsuranPerBulan = $pokokPerBulan + $jasaPerBulan;
            $tanggalMulai = Carbon::parse($p->tanggal_pinjaman);

            $sudahDibayarPerKe = $p->angsuran->keyBy('angsuran_ke');
            $tunggakanPinjaman = 0;
            $punyaJatuhTempo = false;

            for ($ke = 1; $ke <= $jangka; $ke++) {
                $jatuhTempo = $tanggalMulai->copy()->addMonths($ke);
                if ($jatuhTempo->gt($today)) {
                    break;
                }
                if (! $sudahDibayarPerKe->has($ke)) {
                    $tunggakanPinjaman += $angsuranPerBulan;
                    $punyaJatuhTempo = true;
                }
            }

            if ($tunggakanPinjaman > 0) {
                $jumlahTunggakan += $tunggakanPinjaman;
                $jumlahPenunggak++;
            }
            if ($punyaJatuhTempo) {
                $peminjamJatuhTempo++;
                $nilaiJatuhTempo += $tunggakanPinjaman;
            }
        }

        // Persentase Tunggakan: (jumlah tunggakan / total outstanding) * 100. Outstanding = sisa pokok aktif.
        $totalOutstanding = (clone $query)->where('status_pinjaman', 'aktif')->get()->sum(fn ($p) => $p->sisa_pinjaman);
        $persenTunggakan = $totalOutstanding > 0 && $jumlahTunggakan > 0
            ? round(($jumlahTunggakan / $totalOutstanding) * 100, 0)
            : 0;

        // NPL: (pinjaman bermasalah / total pinjaman aktif) * 100. Bermasalah = punya tunggakan.
        $npl = $countPinjamanAktif > 0 && $jumlahPenunggak > 0
            ? round(($jumlahPenunggak / $countPinjamanAktif) * 100, 0)
            : 0;

        return [
            'persen_pengembalian' => min(100, $persenPengembalian),
            'persen_tunggakan' => $persenTunggakan,
            'npl' => $npl,
            'jumlah_peminjam' => $jumlahPeminjam,
            'peminjam_laki' => $peminjamLaki,
            'peminjam_perempuan' => $peminjamPerempuan,
            'peminjam_lunas' => $peminjamLunas,
            'peminjam_belum_lunas' => $peminjamBelumLunas,
            'jumlah_tunggakan' => round($jumlahTunggakan, 0),
            'jumlah_penunggak' => $jumlahPenunggak,
            'peminjam_jatuh_tempo' => $peminjamJatuhTempo,
            'nilai_jatuh_tempo' => round($nilaiJatuhTempo, 0),
        ];
    }

    /**
     * Statistik pinjaman per sektor usaha (jenis usaha).
     * Menggunakan whereIn(id) agar join dengan sektor_usaha tidak ambigu (keduanya punya desa_id).
     *
     * @param  Builder<Pinjaman>|null  $pinjamanQuery
     * @return array<int, array{nama: string, orang: int, rupiah: float}>
     */
    public function getStatistikPerSektor(?Builder $pinjamanQuery = null): array
    {
        $query = $pinjamanQuery ?? Pinjaman::query();

        $pinjamanIds = (clone $query)->pluck('id');
        if ($pinjamanIds->isEmpty()) {
            return [];
        }

        // Tanpa global scope 'desa' agar tidak ada where desa_id (ambigu dengan sektor_usaha.desa_id)
        $rows = Pinjaman::withoutGlobalScope('desa')
            ->whereIn('pinjaman.id', $pinjamanIds)
            ->leftJoin('sektor_usaha', 'pinjaman.sektor_usaha_id', '=', 'sektor_usaha.id')
            ->selectRaw('coalesce(sektor_usaha.nama, \'Lainnya\') as nama')
            ->selectRaw('count(distinct pinjaman.anggota_id) as orang')
            ->selectRaw('coalesce(sum(pinjaman.jumlah_pinjaman), 0) as rupiah')
            ->groupBy('nama')
            ->orderBy('nama')
            ->get();

        $result = [];
        foreach ($rows as $r) {
            $result[] = [
                'nama' => $r->nama,
                'orang' => (int) $r->orang,
                'rupiah' => (float) $r->rupiah,
            ];
        }

        return $result;
    }

    /**
     * Total orang dan rupiah untuk tabel sektor (untuk baris Jumlah).
     *
     * @param  Builder<Pinjaman>|null  $pinjamanQuery
     * @return array{orang: int, rupiah: float}
     */
    public function getTotalUntukSektor(?Builder $pinjamanQuery = null): array
    {
        $query = $pinjamanQuery ?? Pinjaman::query();

        $orang = (int) ((clone $query)->selectRaw('count(distinct pinjaman.anggota_id) as c')->value('c') ?? 0);
        $rupiah = (float) (clone $query)->sum('jumlah_pinjaman');

        return [
            'orang' => $orang,
            'rupiah' => $rupiah,
        ];
    }
}
