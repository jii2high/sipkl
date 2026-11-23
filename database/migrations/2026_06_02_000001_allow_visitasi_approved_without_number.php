<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `disetujui` boleh tanpa nomor surat (F5, Q-L): keputusan Hubin tidak menunggu
 * scan TTD Kepsek, dan masa berlaku dibekukan baru saat surat terbit.
 */
return new class extends Migration
{
    public function up(): void
    {
        // `DROP CONSTRAINT` portabel di MySQL 8.0.19+ dan MariaDB; `DROP CHECK` hanya MySQL.
        DB::statement('ALTER TABLE surat_tugas_visitasi DROP CONSTRAINT ck_stv_nomor');

        DB::statement(<<<'SQL'
            ALTER TABLE surat_tugas_visitasi
                ADD CONSTRAINT ck_stv_nomor
                CHECK (status IN ('diajukan','ditolak','disetujui') OR nomor_surat IS NOT NULL)
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE surat_tugas_visitasi DROP CONSTRAINT ck_stv_nomor');

        DB::statement(<<<'SQL'
            ALTER TABLE surat_tugas_visitasi
                ADD CONSTRAINT ck_stv_nomor
                CHECK (status IN ('diajukan','ditolak') OR nomor_surat IS NOT NULL)
        SQL);
    }
};
