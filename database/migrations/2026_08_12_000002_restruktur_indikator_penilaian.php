<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Level 1: Tujuan Pembelajaran — kategori dihapus, derive dari kompetensi_keahlian_id
        Schema::table('indikator_penilaians', function (Blueprint $table) {
            $table->dropColumn('kategori');
            $table->string('nama');
            $table->string('deskripsi')->nullable()->change();
            $table->softDeletes();
        });

        // Level 2: Indikator Penilaian
        Schema::table('section_penilaians', function (Blueprint $table) {
            $table->string('nama');
            $table->string('deskripsi')->nullable()->change();
            $table->softDeletes();
        });

        // Level 3: Sub-indikator (nullable breakdown dari Indikator tertentu)
        Schema::create('sub_section_penilaians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_penilaian_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('nomor_urut');
            $table->string('nama');
            $table->string('deskripsi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Leaf nilai: sub-indikator (kalau ada), else indikator
        Schema::table('nilai_penilaian', function (Blueprint $table) {
            $table->foreignId('sub_section_penilaian_id')->nullable()->after('section_penilaian_id')->constrained('sub_section_penilaians');
        });
    }

    public function down(): void
    {
        Schema::table('nilai_penilaian', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sub_section_penilaian_id');
        });
        Schema::dropIfExists('sub_section_penilaians');
        Schema::table('section_penilaians', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('nama');
            $table->string('deskripsi')->nullable(false)->change();
        });
        Schema::table('indikator_penilaians', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('nama');
            $table->string('deskripsi')->nullable(false)->change();
            $table->enum('kategori', ['umum', 'teknis_kejuruan']);
        });
    }
};
