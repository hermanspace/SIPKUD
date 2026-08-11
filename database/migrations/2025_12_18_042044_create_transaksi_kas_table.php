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
        Schema::create('transaksi_kas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->constrained('desa')->cascadeOnDelete();
            $table->date('tanggal_transaksi');
            $table->text('uraian');
            $table->enum('jenis_transaksi', ['masuk', 'keluar']);
            $table->decimal('jumlah', 15, 2);

            // Relasi ke pinjaman (untuk kas keluar)
            $table->foreignId('pinjaman_id')->nullable()->constrained('pinjaman')->cascadeOnDelete();

            // Relasi ke angsuran (untuk kas masuk)
            $table->foreignId('angsuran_pinjaman_id')->nullable()->constrained('angsuran_pinjaman')->cascadeOnDelete();

            $table->timestamps();

            // Index untuk performa
            $table->index(['desa_id', 'tanggal_transaksi']);
            $table->index('jenis_transaksi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_kas');
    }
};
