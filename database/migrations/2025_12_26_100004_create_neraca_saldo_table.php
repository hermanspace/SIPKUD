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
        Schema::create('neraca_saldo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->constrained('desa')->cascadeOnDelete();
            $table->foreignId('unit_usaha_id')->nullable()->constrained('unit_usaha')->cascadeOnDelete();
            $table->foreignId('akun_id')->constrained('akun')->restrictOnDelete();
            $table->string('periode', 7)->comment('Format: YYYY-MM'); // 2025-01

            // Saldo
            $table->decimal('saldo_awal_debit', 15, 2)->default(0);
            $table->decimal('saldo_awal_kredit', 15, 2)->default(0);
            $table->decimal('mutasi_debit', 15, 2)->default(0);
            $table->decimal('mutasi_kredit', 15, 2)->default(0);
            $table->decimal('saldo_akhir_debit', 15, 2)->default(0);
            $table->decimal('saldo_akhir_kredit', 15, 2)->default(0);

            // Status periode
            $table->enum('status_periode', ['open', 'closed'])->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->unique(['desa_id', 'akun_id', 'periode', 'unit_usaha_id'], 'neraca_saldo_unique');
            $table->index(['desa_id', 'periode']);
            $table->index(['status_periode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('neraca_saldo');
    }
};
