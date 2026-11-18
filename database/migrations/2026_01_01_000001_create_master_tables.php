<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kelompok "Master" (TRD.md §6.1): academic_periods, companies, signature_assets.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE academic_periods (
                id                        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tahun_ajaran              VARCHAR(9) NOT NULL,
                batch                     ENUM('1','2') NOT NULL,
                tanggal_mulai             DATE NOT NULL,
                tanggal_selesai           DATE NOT NULL,
                is_active                 BOOLEAN NOT NULL DEFAULT TRUE,
                locked_for_new_submission BOOLEAN NOT NULL DEFAULT FALSE,
                created_at                TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_period (tahun_ajaran, batch),
                CONSTRAINT ck_period_tahun CHECK (tahun_ajaran REGEXP '^[0-9]{4}/[0-9]{4}$'),
                CONSTRAINT ck_period_tanggal CHECK (tanggal_selesai >= tanggal_mulai)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE companies (
                id                                     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                nama                                   VARCHAR(200) NOT NULL,
                alamat                                 TEXT NOT NULL,
                email                                  VARCHAR(150) NULL,
                website                                VARCHAR(200) NULL,
                telp_fax                               VARCHAR(30) NULL,
                cp_name                                VARCHAR(150) NULL,
                cp_phone                               VARCHAR(30) NULL,
                pembimbing_industri_nama               VARCHAR(150) NULL,
                pembimbing_industri_email              VARCHAR(150) NULL,
                pembimbing_industri_email_verified_at  TIMESTAMP NULL,
                kepala_industri_nama                   VARCHAR(150) NULL,
                kepala_industri_email                  VARCHAR(150) NULL,
                kepala_industri_email_verified_at      TIMESTAMP NULL,
                source                                 ENUM('siswa','hubin','import') NOT NULL DEFAULT 'hubin',
                created_by                             BIGINT UNSIGNED NULL,
                merged_into_id                         BIGINT UNSIGNED NULL,
                created_at                             TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at                             TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES users(id),
                FOREIGN KEY (merged_into_id) REFERENCES companies(id),
                UNIQUE KEY uq_company_email (email),
                UNIQUE KEY uq_company_website (website),
                UNIQUE KEY uq_company_telp_fax (telp_fax),
                INDEX idx_company_nama (nama),
                -- Aturan "merge ke diri sendiri ditolak" tidak bisa dijaga CHECK di MySQL 8.4
                -- (error 3818: CHECK dilarang menyentuh kolom AUTO_INCREMENT). Ditegakkan di
                -- Action MergeCompany (T2.1). Lihat Catatan Pelaksanaan TASKS.md.
                CONSTRAINT ck_company_pi_signer CHECK (
                    pembimbing_industri_email_verified_at IS NULL
                    OR (pembimbing_industri_nama IS NOT NULL AND pembimbing_industri_email IS NOT NULL)),
                CONSTRAINT ck_company_ki_signer CHECK (
                    kepala_industri_email_verified_at IS NULL
                    OR (kepala_industri_nama IS NOT NULL AND kepala_industri_email IS NOT NULL))
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE signature_assets (
                id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                owner_user_id   BIGINT UNSIGNED NOT NULL,
                file_path       VARCHAR(500) NOT NULL,
                is_active       BOOLEAN NOT NULL DEFAULT TRUE,
                active_owner_id BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN is_active THEN owner_user_id ELSE NULL END) STORED,
                uploaded_by     BIGINT UNSIGNED NOT NULL,
                created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (owner_user_id) REFERENCES users(id),
                FOREIGN KEY (uploaded_by) REFERENCES users(id),
                UNIQUE KEY uq_signature_active_owner (active_owner_id)
            ) ENGINE=InnoDB
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS signature_assets');
        DB::statement('DROP TABLE IF EXISTS companies');
        DB::statement('DROP TABLE IF EXISTS academic_periods');
    }
};
