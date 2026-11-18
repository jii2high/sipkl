<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kelompok "Bimbingan & pengesahan" (TRD.md §6.1): bimbingan_submissions,
 * laporan_versions, document_signatures (5-TTD).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE bimbingan_submissions (
                id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                pengajuan_siswa_id  BIGINT UNSIGNED NOT NULL,
                file_path           VARCHAR(500) NOT NULL,
                keterangan          TEXT NULL,
                is_bagian_akhir     BOOLEAN NOT NULL DEFAULT FALSE,
                status              ENUM('menunggu_feedback','revisi','diterima') NOT NULL DEFAULT 'menunggu_feedback',
                feedback_note       TEXT NULL,
                reviewed_by         BIGINT UNSIGNED NULL,
                reviewed_at         TIMESTAMP NULL,
                is_archived         BOOLEAN NOT NULL DEFAULT FALSE,
                archived_at         TIMESTAMP NULL,
                created_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (pengajuan_siswa_id) REFERENCES pengajuan_siswa(id),
                FOREIGN KEY (reviewed_by) REFERENCES users(id),
                INDEX idx_bimbingan_siswa (pengajuan_siswa_id, is_archived)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE laporan_versions (
                id                              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                pengajuan_siswa_id              BIGINT UNSIGNED NOT NULL,
                version_number                  INT UNSIGNED NOT NULL,
                source_bimbingan_submission_id  BIGINT UNSIGNED NULL,
                file_path                       VARCHAR(500) NOT NULL,
                created_by                      BIGINT UNSIGNED NOT NULL,
                created_at                      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (pengajuan_siswa_id) REFERENCES pengajuan_siswa(id),
                FOREIGN KEY (source_bimbingan_submission_id) REFERENCES bimbingan_submissions(id),
                FOREIGN KEY (created_by) REFERENCES users(id),
                UNIQUE KEY uq_laporan_version (pengajuan_siswa_id, version_number)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE document_signatures (
                id                        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                pengajuan_siswa_id        BIGINT UNSIGNED NOT NULL,
                laporan_version_id        BIGINT UNSIGNED NOT NULL,
                step_order                TINYINT UNSIGNED NOT NULL,
                signer_type               ENUM('guru_pembimbing','kaprog','pembimbing_industri','kepala_industri','kepsek') NOT NULL,
                signer_user_id            BIGINT UNSIGNED NULL,
                signer_external_name      VARCHAR(150) NULL,
                signer_external_contact   VARCHAR(150) NULL,
                status                    ENUM('pending','signed','rejected') NOT NULL DEFAULT 'pending',
                signed_at                 TIMESTAMP NULL,
                rejection_note            TEXT NULL,
                created_at                TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (pengajuan_siswa_id) REFERENCES pengajuan_siswa(id),
                FOREIGN KEY (laporan_version_id) REFERENCES laporan_versions(id),
                FOREIGN KEY (signer_user_id) REFERENCES users(id),
                UNIQUE KEY uq_signature_version_step (laporan_version_id, step_order),
                INDEX idx_sign_siswa_step (pengajuan_siswa_id, step_order, status),
                CONSTRAINT ck_sign_step_type CHECK (
                    (step_order = 1 AND signer_type = 'guru_pembimbing') OR
                    (step_order = 2 AND signer_type = 'kaprog') OR
                    (step_order = 3 AND signer_type = 'pembimbing_industri') OR
                    (step_order = 4 AND signer_type = 'kepala_industri') OR
                    (step_order = 5 AND signer_type = 'kepsek')),
                CONSTRAINT ck_sign_signed CHECK (status <> 'signed' OR signed_at IS NOT NULL),
                CONSTRAINT ck_sign_reject_note CHECK (status <> 'rejected' OR (rejection_note IS NOT NULL AND CHAR_LENGTH(rejection_note) >= 10)),
                CONSTRAINT ck_sign_internal CHECK (
                    step_order IN (3, 4) OR status = 'pending' OR signer_user_id IS NOT NULL)
            ) ENGINE=InnoDB
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS document_signatures');
        DB::statement('DROP TABLE IF EXISTS laporan_versions');
        DB::statement('DROP TABLE IF EXISTS bimbingan_submissions');
    }
};
