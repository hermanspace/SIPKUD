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
        Schema::create('jurnal_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurnal_id')->constrained('jurnal')->cascadeOnDelete();
            $table->foreignId('akun_id')->constrained('akun')->restrictOnDelete();
            $table->enum('posisi', ['debit', 'kredit']);
            $table->decimal('jumlah', 15, 2);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Index untuk performa
            $table->index(['jurnal_id', 'akun_id']);
            $table->index('posisi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_detail');
    }
};
