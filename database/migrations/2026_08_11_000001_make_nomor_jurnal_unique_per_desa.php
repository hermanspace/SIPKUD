<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor jurnal di-generate per desa (JRN/YYYY/MM/XXXXX dimulai dari 00001
     * untuk setiap desa), sehingga unique constraint global pada nomor_jurnal
     * membuat desa kedua yang membuat jurnal pertamanya di bulan yang sama
     * selalu gagal insert. Constraint yang benar adalah unik per desa.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Nama constraint/index lama tidak selalu jurnal_nomor_jurnal_unique:
            // database hasil konversi pgloader (migrasi dari MySQL produksi)
            // membawa nama index bawaan MySQL. Cari semua constraint/index unik
            // single-column pada nomor_jurnal lalu hapus apa pun namanya.
            $lama = DB::select(<<<'SQL'
                select c.conname as name, 'constraint' as kind
                  from pg_constraint c
                  join pg_class t on t.oid = c.conrelid
                 where t.relname = 'jurnal'
                   and c.contype = 'u'
                   and (select array_agg(a.attname)
                          from unnest(c.conkey) k
                          join pg_attribute a on a.attrelid = t.oid and a.attnum = k
                       ) = array['nomor_jurnal']::name[]
                union all
                select i.indexname as name, 'index' as kind
                  from pg_indexes i
                 where i.tablename = 'jurnal'
                   and i.indexdef ilike 'create unique index%'
                   and i.indexdef ~* '\(\s*"?nomor_jurnal"?\s*\)'
                   and not exists (
                        select 1 from pg_constraint c2
                          join pg_class t2 on t2.oid = c2.conrelid
                         where t2.relname = 'jurnal' and c2.conname = i.indexname
                   )
            SQL);

            foreach ($lama as $row) {
                if ($row->kind === 'constraint') {
                    DB::statement(sprintf('alter table jurnal drop constraint "%s"', $row->name));
                } else {
                    DB::statement(sprintf('drop index if exists "%s"', $row->name));
                }
            }

            DB::statement('create unique index if not exists jurnal_desa_id_nomor_jurnal_unique on jurnal (desa_id, nomor_jurnal)');

            return;
        }

        Schema::table('jurnal', function (Blueprint $table) {
            $table->dropUnique(['nomor_jurnal']);
            $table->unique(['desa_id', 'nomor_jurnal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('drop index if exists jurnal_desa_id_nomor_jurnal_unique');
            DB::statement('alter table jurnal drop constraint if exists jurnal_desa_id_nomor_jurnal_unique');
            DB::statement('create unique index if not exists jurnal_nomor_jurnal_unique on jurnal (nomor_jurnal)');

            return;
        }

        Schema::table('jurnal', function (Blueprint $table) {
            $table->dropUnique(['desa_id', 'nomor_jurnal']);
            $table->unique('nomor_jurnal');
        });
    }
};
