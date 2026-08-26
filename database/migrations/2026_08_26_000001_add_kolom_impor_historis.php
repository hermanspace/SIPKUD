<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dukungan impor data historis dari Excel UEK-SP (sheet LPP-UEK):
     * - anggota.nik_sementara: NIK placeholder hasil impor, wajib dilengkapi
     *   sebelum anggota boleh menerima pinjaman baru.
     * - pinjaman.no_sppk: nomor SPPK dari Excel, kunci anti-duplikat per desa.
     * - pinjaman.sumber: penanda asal data (mis. 'import_excel').
     */
    public function up(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->boolean('nik_sementara')->default(false)->after('nik');
        });

        Schema::table('pinjaman', function (Blueprint $table) {
            $table->unsignedInteger('no_sppk')->nullable()->after('nomor_pinjaman');
            $table->string('sumber', 20)->nullable()->after('status_pinjaman');
            $table->unique(['desa_id', 'no_sppk']);
        });
    }

    public function down(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            $table->dropUnique(['desa_id', 'no_sppk']);
            $table->dropColumn(['no_sppk', 'sumber']);
        });

        Schema::table('anggota', function (Blueprint $table) {
            $table->dropColumn('nik_sementara');
        });
    }
};
