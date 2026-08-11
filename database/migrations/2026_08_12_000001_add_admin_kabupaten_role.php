<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Role baru: admin_kabupaten (Dinas PMD) - kelola seluruh desa dan
     * fitur fungsional, tanpa pengaturan sistem & backup database.
     */
    private const ROLES = ['super_admin', 'admin_kabupaten', 'admin_kecamatan', 'admin_desa', 'executive_view'];

    private const ROLES_LAMA = ['super_admin', 'admin_kecamatan', 'admin_desa', 'executive_view'];

    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // DB hasil konversi pgloader membawa kolom role bertipe ENUM
            // PostgreSQL (turunan ENUM MySQL). Normalisasi ke varchar dulu
            // supaya nilai baru bisa masuk dan constraint dikelola seragam.
            $tipe = DB::selectOne(
                "select data_type, udt_name from information_schema.columns
                 where table_name = 'users' and column_name = 'role'"
            );

            if ($tipe && $tipe->data_type === 'USER-DEFINED') {
                DB::statement('ALTER TABLE users ALTER COLUMN role DROP DEFAULT');
                DB::statement('ALTER TABLE users ALTER COLUMN role TYPE varchar(30) USING role::text');
                DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'admin_desa'");
                DB::statement(sprintf('DROP TYPE IF EXISTS "%s"', $tipe->udt_name));
            }

            $this->applyCheckConstraintPgsql(self::ROLES);
        } elseif ($driver === 'sqlite') {
            // SQLite (testing): kolom string tanpa CHECK constraint - tidak perlu apa-apa.
        } else {
            DB::statement(sprintf(
                "ALTER TABLE users MODIFY COLUMN role ENUM('%s') NOT NULL DEFAULT 'admin_desa'",
                implode("', '", self::ROLES)
            ));
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $this->applyCheckConstraintPgsql(self::ROLES_LAMA);
        } elseif ($driver === 'sqlite') {
            // tidak ada yang perlu dikembalikan
        } else {
            DB::statement(sprintf(
                "ALTER TABLE users MODIFY COLUMN role ENUM('%s') NOT NULL DEFAULT 'admin_desa'",
                implode("', '", self::ROLES_LAMA)
            ));
        }
    }

    private function applyCheckConstraintPgsql(array $values): void
    {
        $valuesList = implode("', '", $values);

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('{$valuesList}'))");
    }
};
