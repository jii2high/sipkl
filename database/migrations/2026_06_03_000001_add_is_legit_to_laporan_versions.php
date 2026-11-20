<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kolom `is_legit` (TRD.md §5.4): penanda laporan sudah melewati 5-TTD sehingga
 * masuk pool Siap Sidang. Tidak ada di `docs/schema.sql` versi awal.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE laporan_versions ADD COLUMN is_legit BOOLEAN NOT NULL DEFAULT FALSE AFTER file_path');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE laporan_versions DROP COLUMN is_legit');
    }
};
