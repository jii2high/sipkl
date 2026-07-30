<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_pengantar', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat')->unique();
            $table->foreignId('perusahaan_id')->constrained('perusahaans');
            $table->foreignId('periode_id')->constrained('periode_pkls');
            $table->date('tanggal_terbit');
            $table->foreignId('generated_by')->constrained('users');
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('surat_pengantar_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_pengantar_id')->constrained('surat_pengantar')->cascadeOnDelete();
            $table->foreignId('pengajuan_id')->constrained('pengajuan_pkl')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_pengantar_pengajuan');
        Schema::dropIfExists('surat_pengantar');
    }
};
