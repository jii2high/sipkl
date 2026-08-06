<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasan_pembimbing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_pembimbing_id')->constrained('users');
            $table->foreignId('pengajuan_id')->constrained('pengajuan_pkl')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('surat_tugas_monitoring', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_id')->constrained('periode_pkls');
            $table->enum('lingkup', ['global', 'scoped']);
            $table->foreignId('kompetensi_keahlian_id')->nullable()->constrained('kompetensi_keahlian');
            $table->foreignId('requested_by')->nullable()->constrained('users');
            $table->foreignId('diterbitkan_by')->constrained('users');
            $table->string('nomor_surat')->unique();
            $table->date('tanggal_terbit');
            $table->string('file')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->date('berlaku_hingga')->nullable();
            $table->timestamps();
        });

        Schema::create('kunjungan_monitoring', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penugasan_id')->constrained('penugasan_pembimbing')->cascadeOnDelete();
            $table->foreignId('surat_tugas_id')->constrained('surat_tugas_monitoring');
            $table->date('tanggal_kunjungan');
            $table->text('catatan')->nullable();
            $table->string('dokumentasi_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kunjungan_monitoring');
        Schema::dropIfExists('surat_tugas_monitoring');
        Schema::dropIfExists('penugasan_pembimbing');
    }
};
