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
        Schema::create('pengajuan_pkl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa_profiles')->cascadeOnDelete();
            $table->foreignId('perusahaan_id')->constrained();
            $table->foreignId('periode_id')->constrained('periode_pkls');
            $table->string('status')->default('draft');
            $table->string('nomor_pengajuan')->nullable()->unique();
            $table->foreignId('kaprog_id')->nullable()->constrained('users');
            $table->text('catatan_kaprog')->nullable();
            $table->timestamp('verified_kaprog_at')->nullable();
            $table->foreignId('hubin_id')->nullable()->constrained('users');
            $table->timestamp('surat_dikirim_at')->nullable();
            $table->timestamp('laporan_akhir_uploaded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pengajuan_pkl_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuan_pkl')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users');
            $table->string('status_dari')->nullable();
            $table->string('status_ke');
            $table->text('catatan')->nullable();
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_pkl_histories');
        Schema::dropIfExists('pengajuan_pkl');
    }
};
