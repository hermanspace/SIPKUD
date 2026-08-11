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
                'transaksi_kas',
                'jenis_transaksi',
                ['masuk', 'keluar', 'saldo_awal']
            );
        } elseif ($driver === 'sqlite') {
            // SQLite (testing): rebuild kolom sebagai string tanpa CHECK constraint
            Schema::table('transaksi_kas', function ($table) {
                $table->string('jenis_transaksi')->change();
            });
        } else {
            DB::statement("ALTER TABLE transaksi_kas MODIFY COLUMN jenis_transaksi ENUM('masuk', 'keluar', 'saldo_awal') NOT NULL");
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
                'transaksi_kas',
                'jenis_transaksi',
                ['masuk', 'keluar']
            );
        } elseif ($driver === 'sqlite') {
            // SQLite (testing): tidak ada CHECK constraint yang perlu dikembalikan
        } else {
            DB::statement("ALTER TABLE transaksi_kas MODIFY COLUMN jenis_transaksi ENUM('masuk', 'keluar') NOT NULL");
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
