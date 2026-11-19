<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kelompok "Lain-lain" (TRD.md §6.1): FAQ, ringkasan & ekspor akreditasi, notifikasi,
 * audit log. `activity_log` mengikuti skema nyata spatie/laravel-activitylog v5
 * (kolom `attribute_changes`), bukan v3 (lihat Catatan Pelaksanaan TASKS.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE faq_categories (
                id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                nama        VARCHAR(100) NOT NULL,
                sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
                created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE faqs (
                id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                faq_category_id  BIGINT UNSIGNED NOT NULL,
                pertanyaan       VARCHAR(500) NOT NULL,
                jawaban          TEXT NOT NULL,
                sort_order       INT UNSIGNED NOT NULL DEFAULT 0,
                is_published     BOOLEAN NOT NULL DEFAULT TRUE,
                created_by       BIGINT UNSIGNED NOT NULL,
                created_at       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (faq_category_id) REFERENCES faq_categories(id),
                FOREIGN KEY (created_by) REFERENCES users(id),
                INDEX idx_faq_category_published (faq_category_id, is_published, sort_order)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE sidang_results_summary (
                id                        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                pengajuan_siswa_id        BIGINT UNSIGNED NOT NULL,
                form_submission_id        BIGINT UNSIGNED NOT NULL UNIQUE,
                nama_siswa                VARCHAR(150) NOT NULL,
                nis                       VARCHAR(30) NOT NULL,
                jurusan                   VARCHAR(100) NOT NULL,
                nama_perusahaan           VARCHAR(200) NOT NULL,
                academic_period_id        BIGINT UNSIGNED NOT NULL,
                nilai_total_per_kategori  JSON NOT NULL,
                status_kelulusan          VARCHAR(50) NULL,
                nama_penguji              VARCHAR(150) NOT NULL,
                tanggal_sidang            DATE NOT NULL,
                synced_at                 TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (pengajuan_siswa_id) REFERENCES pengajuan_siswa(id),
                FOREIGN KEY (form_submission_id) REFERENCES form_submissions(id),
                FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id),
                INDEX idx_summary_export (academic_period_id, jurusan, tanggal_sidang)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE accreditation_exports (
                id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                requested_by        BIGINT UNSIGNED NOT NULL,
                academic_period_id  BIGINT UNSIGNED NULL,
                retry_of_id         BIGINT UNSIGNED NULL,
                status              ENUM('queued','running','completed','failed') NOT NULL DEFAULT 'queued',
                snapshot_file_path  VARCHAR(500) NULL,
                snapshot_checksum   CHAR(64) NULL,
                row_count           INT UNSIGNED NULL,
                failure_reason      TEXT NULL,
                requested_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                started_at          TIMESTAMP NULL,
                completed_at        TIMESTAMP NULL,
                expires_at          TIMESTAMP NULL,
                FOREIGN KEY (requested_by) REFERENCES users(id),
                FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id),
                FOREIGN KEY (retry_of_id) REFERENCES accreditation_exports(id),
                INDEX idx_accreditation_export_expiry (status, expires_at),
                CONSTRAINT ck_export_completed CHECK (
                    status <> 'completed' OR (
                        snapshot_file_path IS NOT NULL AND snapshot_checksum IS NOT NULL
                        AND completed_at IS NOT NULL AND expires_at IS NOT NULL))
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE activity_log (
                id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                log_name          VARCHAR(100) NULL,
                description       TEXT NOT NULL,
                subject_type      VARCHAR(150) NULL,
                subject_id        BIGINT UNSIGNED NULL,
                event             VARCHAR(100) NULL,
                causer_type       VARCHAR(150) NULL,
                causer_id         BIGINT UNSIGNED NULL,
                attribute_changes JSON NULL,
                properties        JSON NULL,
                created_at        TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at        TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX activity_log_log_name_index (log_name),
                INDEX idx_activity_subject (subject_type, subject_id),
                INDEX idx_activity_causer (causer_type, causer_id)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE notifications (
                id               CHAR(36) PRIMARY KEY,
                type             VARCHAR(255) NOT NULL,
                notifiable_type  VARCHAR(150) NOT NULL,
                notifiable_id    BIGINT UNSIGNED NOT NULL,
                data             JSON NOT NULL,
                read_at          TIMESTAMP NULL,
                created_at       TIMESTAMP NULL,
                updated_at       TIMESTAMP NULL,
                INDEX idx_notifications_recipient (notifiable_type, notifiable_id, read_at)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE notification_delivery_failures (
                id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                channel            ENUM('email','in_app','push') NOT NULL,
                recipient_email    VARCHAR(150) NULL,
                recipient_user_id  BIGINT UNSIGNED NULL,
                notification_type  VARCHAR(150) NOT NULL,
                related_type       VARCHAR(150) NULL,
                related_id         BIGINT UNSIGNED NULL,
                attempt_count      TINYINT UNSIGNED NOT NULL,
                failure_summary    TEXT NOT NULL,
                first_failed_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_failed_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                resolved_at        TIMESTAMP NULL,
                FOREIGN KEY (recipient_user_id) REFERENCES users(id),
                INDEX idx_notification_failure_monitor (resolved_at, last_failed_at)
            ) ENGINE=InnoDB
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS notification_delivery_failures');
        DB::statement('DROP TABLE IF EXISTS notifications');
        DB::statement('DROP TABLE IF EXISTS activity_log');
        DB::statement('DROP TABLE IF EXISTS accreditation_exports');
        DB::statement('DROP TABLE IF EXISTS sidang_results_summary');
        DB::statement('DROP TABLE IF EXISTS faqs');
        DB::statement('DROP TABLE IF EXISTS faq_categories');
    }
};
