<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE surat_tugas_pembimbing
                DROP INDEX guru_pembimbing_plotting_id,
                ADD COLUMN guru_id BIGINT UNSIGNED NULL AFTER guru_pembimbing_plotting_id,
                ADD COLUMN superseded_at TIMESTAMP NULL AFTER file_path,
                ADD INDEX idx_stp_current (guru_pembimbing_plotting_id, superseded_at)
        SQL);

        DB::statement(<<<'SQL'
            UPDATE surat_tugas_pembimbing AS surat
            INNER JOIN guru_pembimbing_plotting AS plotting
                ON plotting.id = surat.guru_pembimbing_plotting_id
            SET surat.guru_id = plotting.guru_id
            WHERE surat.guru_id IS NULL
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE surat_tugas_pembimbing
                MODIFY COLUMN guru_id BIGINT UNSIGNED NOT NULL,
                ADD CONSTRAINT fk_stp_guru FOREIGN KEY (guru_id) REFERENCES users(id)
        SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE surat_tugas_pembimbing
                DROP INDEX idx_stp_current,
                DROP FOREIGN KEY fk_stp_guru,
                DROP COLUMN guru_id,
                DROP COLUMN superseded_at,
                ADD UNIQUE INDEX guru_pembimbing_plotting_id (guru_pembimbing_plotting_id)
        SQL);
    }
};
