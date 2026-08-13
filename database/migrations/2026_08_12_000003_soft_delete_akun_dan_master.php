<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Akun masuk recycle bin (soft delete)
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('guru_profiles', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('siswa_profiles', function (Blueprint $table) {
            $table->dropColumn('kelas');
            $table->foreignId('periode_id')->nullable()->after('nis')->constrained('periode_pkls')->nullOnDelete();
            $table->softDeletes();
        });

        // Master data juga masuk recycle bin
        Schema::table('perusahaans', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('periode_pkls', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('kompetensi_keahlian', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('kompetensi_keahlian', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('periode_pkls', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('perusahaans', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('siswa_profiles', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('periode_id');
            $table->string('kelas', 20);
        });
        Schema::table('guru_profiles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
