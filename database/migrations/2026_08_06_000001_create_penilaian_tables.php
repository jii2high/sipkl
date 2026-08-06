<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_pkl', function (Blueprint $table) {
            $table->string('laporan_akhir_path')->nullable()->after('laporan_akhir_uploaded_at');
        });

        // BR24: 1 formulir = 1 penugasan pembimbingan.
        Schema::create('formulir_penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penugasan_id')->constrained('penugasan_pembimbing')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('users');
            $table->foreignId('periode_pkl_id')->constrained('periode_pkls');
            $table->foreignId('guru_pembimbing_id')->constrained('users');
            $table->unsignedTinyInteger('sakit')->default(0);
            $table->unsignedTinyInteger('izin')->default(0);
            $table->unsignedTinyInteger('alpa')->default(0);
            $table->text('catatan_guru_pembimbing')->nullable();
            $table->string('nama_pembimbing_industri')->nullable();
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        // BR26: leaf level, terikat ke indikator ATAU section (mutually exclusive).
        Schema::create('nilai_penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formulir_penilaian_id')->constrained('formulir_penilaian')->cascadeOnDelete();
            $table->foreignId('indikator_penilaian_id')->nullable()->constrained('indikator_penilaians');
            $table->foreignId('section_penilaian_id')->nullable()->constrained('section_penilaians');
            $table->decimal('nilai', 5, 2);
            $table->char('predikat', 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_penilaian');
        Schema::dropIfExists('formulir_penilaian');
        Schema::table('pengajuan_pkl', function (Blueprint $table) {
            $table->dropColumn('laporan_akhir_path');
        });
    }
};
