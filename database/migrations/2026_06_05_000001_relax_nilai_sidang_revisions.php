<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permintaan revisi nilai dimulai penguji dari portal (keputusan PO Q-I/F9),
 * sedangkan `docs/schema.sql` awal mengasumsikan Kaprog yang membuat barisnya.
 * Karena itu approver dan tautan unlock menjadi NULL selama status
 * `menunggu_persetujuan`, dan nama status itu ikut ditambahkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE nilai_sidang_revisions
                MODIFY approved_by_kaprog_id BIGINT UNSIGNED NULL,
                MODIFY unlock_magic_link_id BIGINT UNSIGNED NULL,
                MODIFY unlock_expires_at TIMESTAMP NULL,
                MODIFY status ENUM('menunggu_persetujuan','menunggu_revisi','selesai','auto_relocked')
                    NOT NULL DEFAULT 'menunggu_persetujuan'
        SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE nilai_sidang_revisions
                MODIFY approved_by_kaprog_id BIGINT UNSIGNED NOT NULL,
                MODIFY unlock_magic_link_id BIGINT UNSIGNED NOT NULL,
                MODIFY unlock_expires_at TIMESTAMP NOT NULL,
                MODIFY status ENUM('menunggu_revisi','selesai','auto_relocked')
                    NOT NULL DEFAULT 'menunggu_revisi'
        SQL);
    }
};
