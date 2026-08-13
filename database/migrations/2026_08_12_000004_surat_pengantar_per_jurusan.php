<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu jurusan punya satu surat pengantar per perusahaan per periode
        Schema::table('surat_pengantar', function (Blueprint $table) {
            $table->foreignId('kompetensi_keahlian_id')->after('perusahaan_id')->constrained('kompetensi_keahlian');
            $table->unique(['perusahaan_id', 'kompetensi_keahlian_id', 'periode_id'], 'surat_pengantar_per_jurusan_unique');
        });
    }

    public function down(): void
    {
        Schema::table('surat_pengantar', function (Blueprint $table) {
            $table->dropUnique('surat_pengantar_per_jurusan_unique');
            $table->dropConstrainedForeignId('kompetensi_keahlian_id');
        });
    }
};
