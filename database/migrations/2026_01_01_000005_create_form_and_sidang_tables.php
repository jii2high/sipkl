<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kelompok "Form & penilaian" (TRD.md §6.1): external_examiners, jadwal_sidang,
 * form_definitions, form_sections, form_items, form_submissions, form_submission_siswa,
 * nilai_sidang_revisions.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE external_examiners (
                id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                jurusan_id           BIGINT UNSIGNED NOT NULL,
                nama                 VARCHAR(150) NOT NULL,
                nik                  VARCHAR(30) NULL,
                contact_type         ENUM('email','whatsapp') NOT NULL,
                contact_value        VARCHAR(150) NOT NULL,
                contact_verified_at  TIMESTAMP NOT NULL,
                created_by           BIGINT UNSIGNED NOT NULL,
                created_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (jurusan_id) REFERENCES jurusan(id),
                FOREIGN KEY (created_by) REFERENCES users(id)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE jadwal_sidang (
                id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                pengajuan_siswa_id    BIGINT UNSIGNED NOT NULL UNIQUE,
                external_examiner_id  BIGINT UNSIGNED NOT NULL,
                tanggal               DATE NOT NULL,
                urutan                INT UNSIGNED NOT NULL,
                status                ENUM('terjadwal','sesi_aktif','selesai') NOT NULL DEFAULT 'terjadwal',
                scheduled_by          BIGINT UNSIGNED NOT NULL,
                created_at            TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at            TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (pengajuan_siswa_id) REFERENCES pengajuan_siswa(id),
                FOREIGN KEY (external_examiner_id) REFERENCES external_examiners(id),
                FOREIGN KEY (scheduled_by) REFERENCES users(id),
                INDEX idx_jadwal_tanggal_urutan (tanggal, urutan)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE form_definitions (
                id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                jurusan_id          BIGINT UNSIGNED NOT NULL,
                academic_period_id  BIGINT UNSIGNED NOT NULL,
                form_type           ENUM('sidang','penilaian_industri') NOT NULL,
                nama                VARCHAR(150) NOT NULL,
                has_kehadiran       BOOLEAN NOT NULL DEFAULT FALSE,
                has_catatan         BOOLEAN NOT NULL DEFAULT FALSE,
                is_published        BOOLEAN NOT NULL DEFAULT FALSE,
                published_at        TIMESTAMP NULL,
                created_by          BIGINT UNSIGNED NOT NULL,
                created_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (jurusan_id) REFERENCES jurusan(id),
                FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id),
                FOREIGN KEY (created_by) REFERENCES users(id),
                UNIQUE KEY uq_form_definition_type (id, form_type)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE form_sections (
                id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                form_definition_id  BIGINT UNSIGNED NOT NULL,
                label               VARCHAR(150) NOT NULL,
                sort_order          INT UNSIGNED NOT NULL DEFAULT 0,
                FOREIGN KEY (form_definition_id) REFERENCES form_definitions(id),
                INDEX idx_section_form (form_definition_id, sort_order)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE form_items (
                id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                form_section_id    BIGINT UNSIGNED NOT NULL,
                label              VARCHAR(255) NOT NULL,
                has_alokasi_waktu  BOOLEAN NOT NULL DEFAULT FALSE,
                sort_order         INT UNSIGNED NOT NULL DEFAULT 0,
                FOREIGN KEY (form_section_id) REFERENCES form_sections(id),
                INDEX idx_item_section (form_section_id, sort_order)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE form_submissions (
                id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                form_definition_id  BIGINT UNSIGNED NOT NULL,
                form_type           ENUM('sidang','penilaian_industri') NOT NULL,
                jadwal_sidang_id    BIGINT UNSIGNED NULL,
                pengajuan_siswa_id  BIGINT UNSIGNED NULL,
                company_id          BIGINT UNSIGNED NULL,
                academic_period_id  BIGINT UNSIGNED NULL,
                jawaban             JSON NOT NULL,
                nilai_total         DECIMAL(6,2) GENERATED ALWAYS AS (
                                        CAST(JSON_UNQUOTE(JSON_EXTRACT(jawaban, '$.nilai_total')) AS DECIMAL(6,2))
                                    ) STORED,
                is_submitted        BOOLEAN NOT NULL DEFAULT FALSE,
                submitted_at        TIMESTAMP NULL,
                revision_count      INT UNSIGNED NOT NULL DEFAULT 0,
                created_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (form_definition_id, form_type) REFERENCES form_definitions(id, form_type),
                FOREIGN KEY (jadwal_sidang_id) REFERENCES jadwal_sidang(id),
                FOREIGN KEY (pengajuan_siswa_id) REFERENCES pengajuan_siswa(id),
                FOREIGN KEY (company_id) REFERENCES companies(id),
                FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id),
                UNIQUE KEY uq_form_submission_type (id, form_type),
                UNIQUE KEY uq_submission_jadwal (jadwal_sidang_id),
                UNIQUE KEY uq_submission_industri (form_definition_id, company_id, academic_period_id),
                INDEX idx_nilai_total (nilai_total),
                CONSTRAINT ck_submission_shape CHECK (
                    (form_type = 'sidang'
                        AND jadwal_sidang_id IS NOT NULL AND pengajuan_siswa_id IS NOT NULL
                        AND company_id IS NULL AND academic_period_id IS NULL)
                    OR
                    (form_type = 'penilaian_industri'
                        AND jadwal_sidang_id IS NULL AND pengajuan_siswa_id IS NULL
                        AND company_id IS NOT NULL AND academic_period_id IS NOT NULL)),
                CONSTRAINT ck_submission_submitted CHECK (is_submitted = FALSE OR submitted_at IS NOT NULL)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE form_submission_siswa (
                id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                form_submission_id  BIGINT UNSIGNED NOT NULL,
                form_type           ENUM('sidang','penilaian_industri') NOT NULL DEFAULT 'penilaian_industri',
                pengajuan_siswa_id  BIGINT UNSIGNED NOT NULL,
                FOREIGN KEY (form_submission_id, form_type) REFERENCES form_submissions(id, form_type),
                FOREIGN KEY (pengajuan_siswa_id) REFERENCES pengajuan_siswa(id),
                UNIQUE KEY uq_submission_siswa (form_submission_id, pengajuan_siswa_id),
                CONSTRAINT ck_pivot_type CHECK (form_type = 'penilaian_industri')
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE nilai_sidang_revisions (
                id                     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                form_submission_id     BIGINT UNSIGNED NOT NULL,
                jawaban_lama           JSON NOT NULL,
                jawaban_baru           JSON NULL,
                reported_by            ENUM('penguji','siswa') NOT NULL,
                reason                 TEXT NOT NULL,
                approved_by_kaprog_id  BIGINT UNSIGNED NOT NULL,
                unlock_magic_link_id   BIGINT UNSIGNED NOT NULL,
                unlock_expires_at      TIMESTAMP NOT NULL,
                status                 ENUM('menunggu_revisi','selesai','auto_relocked') NOT NULL DEFAULT 'menunggu_revisi',
                created_at             TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (form_submission_id) REFERENCES form_submissions(id),
                FOREIGN KEY (approved_by_kaprog_id) REFERENCES users(id),
                INDEX idx_revisi_status_expiry (status, unlock_expires_at)
            ) ENGINE=InnoDB
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS nilai_sidang_revisions');
        DB::statement('DROP TABLE IF EXISTS form_submission_siswa');
        DB::statement('DROP TABLE IF EXISTS form_submissions');
        DB::statement('DROP TABLE IF EXISTS form_items');
        DB::statement('DROP TABLE IF EXISTS form_sections');
        DB::statement('DROP TABLE IF EXISTS form_definitions');
        DB::statement('DROP TABLE IF EXISTS jadwal_sidang');
        DB::statement('DROP TABLE IF EXISTS external_examiners');
    }
};
