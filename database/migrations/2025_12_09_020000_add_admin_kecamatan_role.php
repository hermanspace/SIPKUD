<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $this->alterCheckConstraintPgsql(
                'users',
                'role',
                ['super_admin', 'admin_kecamatan', 'admin_desa', 'executive_view']
            );
        } elseif ($driver === 'sqlite') {
            // SQLite (testing): rebuild kolom sebagai string tanpa CHECK constraint
            Schema::table('users', function ($table) {
                $table->string('role')->default('admin_desa')->change();
            });
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin_kecamatan', 'admin_desa', 'executive_view') NOT NULL DEFAULT 'admin_desa'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $this->alterCheckConstraintPgsql(
                'users',
                'role',
                ['super_admin', 'admin_desa', 'executive_view']
            );
        } elseif ($driver === 'sqlite') {
            // SQLite (testing): tidak ada CHECK constraint yang perlu dikembalikan
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin_desa', 'executive_view') NOT NULL DEFAULT 'admin_desa'");
        }
    }

    private function alterCheckConstraintPgsql(string $table, string $column, array $values): void
    {
        $constraintName = "{$table}_{$column}_check";
        $valuesList = implode("', '", array_map(fn ($v) => addslashes($v), $values));

        DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraintName}");
        DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraintName} CHECK ({$column} IN ('{$valuesList}'))");
    }
};
