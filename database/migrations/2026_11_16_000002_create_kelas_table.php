<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * schema.sql Bagian 1 — kelas.
 *
 * Tidak ada kolom `walas_id` (PRD Lampiran A11): walas adalah baris
 * user_roles(role='walas', kelas_id=...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurusan_id')->constrained('jurusan');
            $table->string('nama', 50);
            $table->unsignedTinyInteger('tingkat');
            $table->timestamp('created_at')->nullable();

            $table->unique(['jurusan_id', 'nama'], 'uq_kelas_nama');
        });

        DB::statement('ALTER TABLE kelas ADD CONSTRAINT ck_kelas_tingkat CHECK (tingkat IN (10, 11, 12))');
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
