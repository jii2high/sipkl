<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kelompok "Surat & dokumen" (TRD.md §6.1): nomor_surat_counters, surat_tugas_pembimbing,
 * surat_tugas_visitasi (+companies), visitasi_dokumentasi, document_qr_tokens.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE nomor_surat_counters (
                id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tahun_ajaran  VARCHAR(9) NOT NULL,
                jenis_surat   ENUM('surat_pengantar','surat_tugas_pembimbing','surat_tugas_visitasi') NOT NULL,
                last_number   INT UNSIGNED NOT NULL DEFAULT 0,
                UNIQUE KEY uq_counter (tahun_ajaran, jenis_surat)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE surat_tugas_pembimbing (
                id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                guru_pembimbing_plotting_id BIGINT UNSIGNED NOT NULL UNIQUE,
                tahun_ajaran                VARCHAR(9) NOT NULL,
                nomor_urut                  INT UNSIGNED NOT NULL,
                nomor_surat                 VARCHAR(100) NOT NULL,
                signature_asset_id          BIGINT UNSIGNED NOT NULL,
                signed_at                   TIMESTAMP NOT NULL,
                file_path                   VARCHAR(500) NULL,
                created_at                  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (guru_pembimbing_plotting_id) REFERENCES guru_pembimbing_plotting(id),
                FOREIGN KEY (signature_asset_id) REFERENCES signature_assets(id),
                UNIQUE KEY uq_stp_nomor_urut (tahun_ajaran, nomor_urut),
                UNIQUE KEY uq_stp_nomor_surat (tahun_ajaran, nomor_surat)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE surat_tugas_visitasi (
                id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                guru_id             BIGINT UNSIGNED NOT NULL,
                diajukan_oleh       ENUM('kaprog','hubin') NOT NULL,
                kaprog_id           BIGINT UNSIGNED NULL,
                is_general          BOOLEAN NOT NULL DEFAULT FALSE,
                tahun_ajaran        VARCHAR(9) NULL,
                nomor_urut          INT UNSIGNED NULL,
                nomor_surat         VARCHAR(100) NULL,
                tanggal_mulai       DATE NOT NULL,
                tanggal_selesai     DATE NOT NULL,
                status              ENUM('diajukan','ditolak','disetujui','dicetak','aktif','kedaluwarsa') NOT NULL DEFAULT 'diajukan',
                approved_by         BIGINT UNSIGNED NULL,
                approved_at         TIMESTAMP NULL,
                rejection_note      TEXT NULL,
                signature_asset_id  BIGINT UNSIGNED NULL,
                file_path           VARCHAR(500) NULL,
                created_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (guru_id) REFERENCES users(id),
                FOREIGN KEY (kaprog_id) REFERENCES users(id),
                FOREIGN KEY (approved_by) REFERENCES users(id),
                FOREIGN KEY (signature_asset_id) REFERENCES signature_assets(id),
                UNIQUE KEY uq_stv_nomor_urut (tahun_ajaran, nomor_urut),
                UNIQUE KEY uq_stv_nomor_surat (tahun_ajaran, nomor_surat),
                INDEX idx_stv_guru_status (guru_id, status),
                CONSTRAINT ck_stv_tanggal CHECK (tanggal_selesai >= tanggal_mulai),
                CONSTRAINT ck_stv_kaprog CHECK (diajukan_oleh <> 'kaprog' OR kaprog_id IS NOT NULL),
                CONSTRAINT ck_stv_reject_note CHECK (status <> 'ditolak' OR rejection_note IS NOT NULL),
                CONSTRAINT ck_stv_nomor CHECK (status IN ('diajukan','ditolak') OR nomor_surat IS NOT NULL)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE surat_tugas_visitasi_companies (
                id                       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                surat_tugas_visitasi_id  BIGINT UNSIGNED NOT NULL,
                company_id               BIGINT UNSIGNED NOT NULL,
                FOREIGN KEY (surat_tugas_visitasi_id) REFERENCES surat_tugas_visitasi(id),
                FOREIGN KEY (company_id) REFERENCES companies(id),
                UNIQUE KEY uq_stv_company (surat_tugas_visitasi_id, company_id)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE visitasi_dokumentasi (
                id                       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                surat_tugas_visitasi_id  BIGINT UNSIGNED NOT NULL,
                company_id               BIGINT UNSIGNED NOT NULL,
                uploaded_by              BIGINT UNSIGNED NOT NULL,
                tanggal_kunjungan        DATE NOT NULL,
                file_path                VARCHAR(500) NOT NULL,
                keterangan               TEXT NULL,
                created_at               TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (surat_tugas_visitasi_id) REFERENCES surat_tugas_visitasi(id),
                FOREIGN KEY (company_id) REFERENCES companies(id),
                FOREIGN KEY (uploaded_by) REFERENCES users(id),
                INDEX idx_visitasi_dok (surat_tugas_visitasi_id, company_id)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE document_qr_tokens (
                id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                token_hash     CHAR(64) NOT NULL UNIQUE,
                document_type  ENUM('surat_pengantar','surat_tugas_pembimbing','surat_tugas_visitasi') NOT NULL,
                document_id    BIGINT UNSIGNED NOT NULL,
                nomor_surat    VARCHAR(100) NOT NULL,
                status         ENUM('valid','dicabut') NOT NULL DEFAULT 'valid',
                issued_at      TIMESTAMP NOT NULL,
                revoked_at     TIMESTAMP NULL,
                revoked_by     BIGINT UNSIGNED NULL,
                revoke_reason  TEXT NULL,
                FOREIGN KEY (revoked_by) REFERENCES users(id),
                UNIQUE KEY uq_qr_document (document_type, document_id)
            ) ENGINE=InnoDB
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS document_qr_tokens');
        DB::statement('DROP TABLE IF EXISTS visitasi_dokumentasi');
        DB::statement('DROP TABLE IF EXISTS surat_tugas_visitasi_companies');
        DB::statement('DROP TABLE IF EXISTS surat_tugas_visitasi');
        DB::statement('DROP TABLE IF EXISTS surat_tugas_pembimbing');
        DB::statement('DROP TABLE IF EXISTS nomor_surat_counters');
    }
};
