<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kelompok "Akses eksternal" (TRD.md §6.1): magic_links, external_sessions.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE magic_links (
                id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                token_hash           CHAR(64) NOT NULL UNIQUE,
                type                 ENUM('examiner_sidang','examiner_unlock','signer_step3',
                                          'signer_step4','industry_assessment') NOT NULL,
                reference_id         BIGINT UNSIGNED NOT NULL,
                pin_hash             CHAR(64) NULL,
                pin_attempt_count    TINYINT UNSIGNED NOT NULL DEFAULT 0,
                device_fingerprint   VARCHAR(255) NULL,
                expires_at           TIMESTAMP NOT NULL,
                used_at              TIMESTAMP NULL,
                revoked_at           TIMESTAMP NULL,
                revoked_by           BIGINT UNSIGNED NULL,
                revoke_reason        TEXT NULL,
                reissued_from_id     BIGINT UNSIGNED NULL,
                created_by           BIGINT UNSIGNED NULL,
                is_active            BOOLEAN NOT NULL DEFAULT TRUE,
                active_reference_id  BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN is_active THEN reference_id ELSE NULL END) STORED,
                created_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (revoked_by) REFERENCES users(id),
                FOREIGN KEY (reissued_from_id) REFERENCES magic_links(id),
                FOREIGN KEY (created_by) REFERENCES users(id),
                INDEX idx_magic_type_ref (type, reference_id),
                UNIQUE KEY uq_magic_active_action_target (type, active_reference_id)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE external_sessions (
                id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                magic_link_id       BIGINT UNSIGNED NOT NULL,
                otp_code_hash       CHAR(64) NOT NULL,
                otp_expires_at      TIMESTAMP NOT NULL,
                otp_attempt_count   TINYINT UNSIGNED NOT NULL DEFAULT 0,
                verified_at         TIMESTAMP NULL,
                session_expires_at  TIMESTAMP NULL,
                revoked_at          TIMESTAMP NULL,
                created_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (magic_link_id) REFERENCES magic_links(id),
                INDEX idx_ext_session_link (magic_link_id, revoked_at)
            ) ENGINE=InnoDB
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS external_sessions');
        DB::statement('DROP TABLE IF EXISTS magic_links');
    }
};
