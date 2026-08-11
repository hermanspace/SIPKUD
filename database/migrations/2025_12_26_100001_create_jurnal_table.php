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
        Schema::create('jurnal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->constrained('desa')->cascadeOnDelete();
            $table->foreignId('unit_usaha_id')->nullable()->constrained('unit_usaha')->cascadeOnDelete();
            $table->string('nomor_jurnal')->unique()->comment('Auto-generated: JRN/YYYY/MM/XXXXX');
            $table->date('tanggal_transaksi')->index();
            $table->enum('jenis_jurnal', ['kas_harian', 'memorial', 'penyesuaian', 'penutup'])
                ->default('kas_harian')
                ->comment('kas_harian: dari transaksi kas, memorial: non-kas, penyesuaian: jurnal penyesuaian, penutup: tutup buku');
            $table->text('keterangan');
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_kredit', 15, 2)->default(0);
            $table->enum('status', ['draft', 'posted', 'void'])->default('posted');

            // Relasi opsional ke transaksi kas (jika dari kas)
            $table->foreignId('transaksi_kas_id')->nullable()->constrained('transaksi_kas')->cascadeOnDelete();

            // Relasi opsional ke pinjaman (jika terkait pinjaman)
            $table->foreignId('pinjaman_id')->nullable()->constrained('pinjaman')->cascadeOnDelete();

            // Relasi opsional ke angsuran (jika terkait angsuran)
            $table->foreignId('angsuran_pinjaman_id')->nullable()->constrained('angsuran_pinjaman')->cascadeOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Index untuk performa query
            $table->index(['desa_id', 'tanggal_transaksi', 'status']);
            $table->index('jenis_jurnal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal');
    }
};
