<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registrasi aset tetap + penyusutan garis lurus bulanan.
     * Penyusutan dijurnal otomatis (Beban Penyusutan / Akumulasi Penyusutan).
     */
    public function up(): void
    {
        Schema::create('aset_tetap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->constrained('desa')->cascadeOnDelete();
            $table->foreignId('unit_usaha_id')->nullable()->constrained('unit_usaha')->nullOnDelete();
            $table->string('nama_aset');
            $table->date('tanggal_perolehan');
            $table->decimal('harga_perolehan', 15, 2);
            $table->decimal('nilai_residu', 15, 2)->default(0);
            $table->unsignedSmallInteger('umur_bulan');
            $table->foreignId('akun_aset_id')->constrained('akun')->restrictOnDelete();
            $table->foreignId('akun_akumulasi_id')->constrained('akun')->restrictOnDelete();
            $table->foreignId('akun_beban_id')->constrained('akun')->restrictOnDelete();
            $table->decimal('akumulasi_tercatat', 15, 2)->default(0);
            $table->string('periode_penyusutan_terakhir', 7)->nullable()->comment('YYYY-MM');
            $table->enum('status', ['aktif', 'dilepas'])->default('aktif');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['desa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aset_tetap');
    }
};
