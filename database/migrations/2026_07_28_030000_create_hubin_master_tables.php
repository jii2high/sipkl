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
        Schema::create('periode_pkl', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('status_aktif')->default(false);
            $table->timestamps();
        });

        Schema::create('perusahaan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->string('telp')->nullable();
            $table->unsignedTinyInteger('kuota_saran')->default(6);
            $table->timestamps();
        });

        Schema::create('indikator_penilaian', function (Blueprint $table) {
            $table->id();
            $table->enum('kategori', ['umum', 'teknis_kejuruan']);
            $table->foreignId('kompetensi_keahlian_id')->nullable()->constrained('kompetensi_keahlian');
            $table->unsignedTinyInteger('nomor_urut');
            $table->string('deskripsi');
            $table->timestamps();
        });

        Schema::create('section_penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indikator_penilaian_id')->constrained('indikator_penilaian')->cascadeOnDelete();
            $table->unsignedTinyInteger('nomor_urut');
            $table->string('deskripsi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_penilaian');
        Schema::dropIfExists('indikator_penilaian');
        Schema::dropIfExists('perusahaan');
        Schema::dropIfExists('periode_pkl');
    }
};
