<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * schema.sql Bagian 2 — user_roles (1 user boleh banyak role).
 *
 * `jurusan_key`/`kelas_key` adalah generated column (IFNULL scope → 0) supaya
 * UNIQUE tetap efektif saat scope NULL (PRD Lampiran A11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('role', ['siswa', 'walas', 'guru_pembimbing', 'kaprog', 'hubin', 'kepsek']);
            $table->foreignId('jurusan_id')->nullable()->constrained('jurusan');
            $table->foreignId('kelas_id')->nullable()->constrained('kelas');
            $table->unsignedBigInteger('jurusan_key')->storedAs('IFNULL(jurusan_id, 0)');
            $table->unsignedBigInteger('kelas_key')->storedAs('IFNULL(kelas_id, 0)');
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'role', 'jurusan_key', 'kelas_key'], 'uq_user_role_scope');
        });

        DB::statement(
            'ALTER TABLE user_roles ADD CONSTRAINT ck_role_scope CHECK ('
            ."(role <> 'kaprog' OR jurusan_id IS NOT NULL) "
            ."AND (role <> 'walas' OR kelas_id IS NOT NULL))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
