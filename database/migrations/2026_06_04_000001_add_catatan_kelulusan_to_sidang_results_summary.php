<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Catatan kelulusan sidang (Q-I): Kaprog memilih Lulus/Tidak Lulus dan boleh
 * menambah catatan. Tidak ada di `docs/schema.sql` versi awal.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE sidang_results_summary ADD COLUMN catatan_kelulusan TEXT NULL AFTER status_kelulusan');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE sidang_results_summary DROP COLUMN catatan_kelulusan');
    }
};
