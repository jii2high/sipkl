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
        Schema::create('kompetensi_keahlian', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('acronym', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('guru_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nip', 30)->nullable();
            $table->enum('jabatan', ['kaprog', 'guru_pembimbing', 'hubin']);
            $table->foreignId('kompetensi_keahlian_id')->nullable()->constrained('kompetensi_keahlian');
            $table->timestamps();
        });

        Schema::create('siswa_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nis', 30)->unique();
            $table->string('kelas', 20);
            $table->foreignId('kompetensi_keahlian_id')->constrained('kompetensi_keahlian');
            $table->text('alamat')->nullable();
            $table->string('no_telp', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa_profiles');
        Schema::dropIfExists('guru_profiles');
        Schema::dropIfExists('kompetensi_keahlian');
    }
};
