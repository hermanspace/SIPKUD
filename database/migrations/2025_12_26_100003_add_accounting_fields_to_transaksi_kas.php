<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transaksi_kas', function (Blueprint $table) {
            // Tambah foreign key ke akun kas (untuk kas masuk/keluar)
            $table->foreignId('akun_kas_id')
                ->nullable()
                ->after('jenis_transaksi')
                ->constrained('akun')
                ->restrictOnDelete()
                ->comment('Akun kas/bank yang digunakan');

            // Tambah foreign key ke akun lawan (untuk double entry)
            $table->foreignId('akun_lawan_id')
                ->nullable()
                ->after('akun_kas_id')
                ->constrained('akun')
                ->restrictOnDelete()
                ->comment('Akun lawan (pendapatan/biaya/dll)');

            // Tambah unit usaha
            $table->foreignId('unit_usaha_id')
                ->nullable()
                ->after('desa_id')
                ->constrained('unit_usaha')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_kas', function (Blueprint $table) {
            $table->dropForeign(['akun_kas_id']);
            $table->dropColumn('akun_kas_id');

            $table->dropForeign(['akun_lawan_id']);
            $table->dropColumn('akun_lawan_id');

            $table->dropForeign(['unit_usaha_id']);
            $table->dropColumn('unit_usaha_id');
        });
    }
};
