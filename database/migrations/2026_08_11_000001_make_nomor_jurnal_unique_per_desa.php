<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('jurnal', function (Blueprint $table) {
            $table->dropUnique(['desa_id', 'nomor_jurnal']);
            $table->unique('nomor_jurnal');
        });
    }
};
