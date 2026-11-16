<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * schema.sql Bagian 2 — users.
 *
 * - Email wajib domain sekolah (CHECK).
 * - Siswa wajib punya jurusan + kelas (CHECK).
 * - `remember_token` ditambahkan untuk kompatibilitas session auth Laravel
 *   (tidak ada di schema.sql).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nis_nip', 30)->unique();
            $table->string('name', 150);
            $table->string('email', 150)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('user_type', ['siswa', 'guru', 'hubin', 'kepsek']);
            $table->foreignId('jurusan_id')->nullable()->constrained('jurusan');
            $table->foreignId('kelas_id')->nullable()->constrained('kelas');
            $table->boolean('is_first_login')->default(true);
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT ck_users_domain CHECK (email LIKE '%@smkn1kotabekasi.sch.id')");
        DB::statement("ALTER TABLE users ADD CONSTRAINT ck_users_siswa_scope CHECK (user_type <> 'siswa' OR (jurusan_id IS NOT NULL AND kelas_id IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
