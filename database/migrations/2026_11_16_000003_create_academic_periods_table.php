<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * schema.sql Bagian 1 — academic_periods (tahun ajaran + batch, PD-02).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_periods', function (Blueprint $table) {
            $table->id();
            $table->string('tahun_ajaran', 9);
            $table->enum('batch', ['1', '2']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('is_active')->default(true);
            $table->boolean('locked_for_new_submission')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->unique(['tahun_ajaran', 'batch'], 'uq_period');
        });

        DB::statement("ALTER TABLE academic_periods ADD CONSTRAINT ck_period_tahun CHECK (tahun_ajaran REGEXP '^[0-9]{4}/[0-9]{4}$')");
        DB::statement('ALTER TABLE academic_periods ADD CONSTRAINT ck_period_tanggal CHECK (tanggal_selesai >= tanggal_mulai)');
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_periods');
    }
};
