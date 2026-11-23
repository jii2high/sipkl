<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Preferensi push opt-in per akun (BR-F14-1, T10.3). NULL = belum pernah
 * ditanya, TRUE/FALSE = jawaban pengguna; jawaban tidak pernah ditimpa otomatis.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users ADD COLUMN push_opt_in BOOLEAN NULL AFTER theme_preference');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP COLUMN push_opt_in');
    }
};
