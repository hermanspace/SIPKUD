<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restrukturisasi Master Akun (COA) menjadi global.
     * Akun digunakan oleh seluruh desa - hanya Admin dan Super Admin yang bisa add/edit.
     */
    public function up(): void
    {
        // Jika kolom desa_id sudah tidak ada (migration pernah dijalankan), skip
        if (! Schema::hasColumn('akun', 'desa_id')) {
            return;
        }

        // 1. Tangani duplikat kode_akun - pertahankan satu per kode (id terkecil)
        $duplicates = DB::table('akun')
            ->select('kode_akun', DB::raw('MIN(id) as keeper_id'))
            ->groupBy('kode_akun')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $duplicateIds = DB::table('akun')
                ->where('kode_akun', $dup->kode_akun)
                ->where('id', '!=', $dup->keeper_id)
                ->pluck('id');

            foreach ($duplicateIds as $oldId) {
                DB::table('jurnal_detail')->where('akun_id', $oldId)->update(['akun_id' => $dup->keeper_id]);
                if (Schema::hasColumn('transaksi_kas', 'akun_kas_id')) {
                    DB::table('transaksi_kas')->where('akun_kas_id', $oldId)->update(['akun_kas_id' => $dup->keeper_id]);
                    DB::table('transaksi_kas')->where('akun_lawan_id', $oldId)->update(['akun_lawan_id' => $dup->keeper_id]);
                }

                // neraca_saldo: gabungkan saldo lalu hapus duplikat
                $neracaRows = DB::table('neraca_saldo')->where('akun_id', $oldId)->get();
                foreach ($neracaRows as $row) {
                    $existing = DB::table('neraca_saldo')
                        ->where('desa_id', $row->desa_id)
                        ->where('akun_id', $dup->keeper_id)
                        ->where('periode', $row->periode)
                        ->where('unit_usaha_id', $row->unit_usaha_id)
                        ->first();
                    if ($existing) {
                        DB::table('neraca_saldo')->where('id', $existing->id)->update([
                            'saldo_awal_debit' => $existing->saldo_awal_debit + $row->saldo_awal_debit,
                            'saldo_awal_kredit' => $existing->saldo_awal_kredit + $row->saldo_awal_kredit,
                            'mutasi_debit' => $existing->mutasi_debit + $row->mutasi_debit,
                            'mutasi_kredit' => $existing->mutasi_kredit + $row->mutasi_kredit,
                            'saldo_akhir_debit' => $existing->saldo_akhir_debit + $row->saldo_akhir_debit,
                            'saldo_akhir_kredit' => $existing->saldo_akhir_kredit + $row->saldo_akhir_kredit,
                        ]);
                    } else {
                        DB::table('neraca_saldo')->where('id', $row->id)->update(['akun_id' => $dup->keeper_id]);
                    }
                }
                DB::table('neraca_saldo')->where('akun_id', $oldId)->delete();

                DB::table('akun')->where('id', $oldId)->delete();
            }
        }

        // 2. Hapus foreign key dan constraint
        Schema::table('akun', function (Blueprint $table) {
            $table->dropForeign(['desa_id']);
            $table->dropUnique(['desa_id', 'kode_akun']);
        });

        // 3. Hapus kolom desa_id
        Schema::table('akun', function (Blueprint $table) {
            $table->dropColumn('desa_id');
        });

        // 4. Tambah unique kode_akun global
        Schema::table('akun', function (Blueprint $table) {
            $table->unique('kode_akun');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('akun', 'desa_id')) {
            return; // sudah punya desa_id, tidak perlu rollback
        }

        Schema::table('akun', function (Blueprint $table) {
            $table->dropUnique(['kode_akun']);
        });

        // Add desa_id (after() ignored on PostgreSQL - column order doesn't affect functionality)
        Schema::table('akun', function (Blueprint $table) {
            $table->foreignId('desa_id')->nullable()->constrained('desa')->onDelete('cascade');
        });

        // Set desa_id ke desa pertama untuk data yang ada (fallback)
        $firstDesaId = DB::table('desa')->value('id');
        if ($firstDesaId) {
            DB::table('akun')->whereNull('desa_id')->update(['desa_id' => $firstDesaId]);
        }

        // Set NOT NULL - driver-specific (avoid change() which requires doctrine/dbal)
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE akun ALTER COLUMN desa_id SET NOT NULL');
        } else {
            DB::statement('ALTER TABLE akun MODIFY desa_id BIGINT UNSIGNED NOT NULL');
        }

        Schema::table('akun', function (Blueprint $table) {
            $table->unique(['desa_id', 'kode_akun']);
        });
    }
};
