<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kelompok "Pengajuan" (TRD.md §6.1): pengajuan_pkl (company group), pengajuan_siswa,
 * guru_pembimbing_plotting, plus VIEW kuota (hanya untuk baca).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE pengajuan_pkl (
                id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id          BIGINT UNSIGNED NOT NULL,
                academic_period_id  BIGINT UNSIGNED NOT NULL,
                status              ENUM('terbentuk','surat_terbit','berjalan','bubar') NOT NULL DEFAULT 'terbentuk',
                tahun_ajaran        VARCHAR(9) NULL,
                nomor_urut          INT UNSIGNED NULL,
                nomor_surat         VARCHAR(100) NULL,
                signature_asset_id  BIGINT UNSIGNED NULL,
                file_path           VARCHAR(500) NULL,
                issued_by           BIGINT UNSIGNED NULL,
                issued_at           TIMESTAMP NULL,
                sent_at             TIMESTAMP NULL,
                created_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES companies(id),
                FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id),
                FOREIGN KEY (signature_asset_id) REFERENCES signature_assets(id),
                FOREIGN KEY (issued_by) REFERENCES users(id),
                UNIQUE KEY uq_group_scope (id, company_id, academic_period_id),
                UNIQUE KEY uq_group_nomor_urut (tahun_ajaran, nomor_urut),
                UNIQUE KEY uq_group_nomor_surat (tahun_ajaran, nomor_surat),
                INDEX idx_group_company_period (company_id, academic_period_id)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE pengajuan_siswa (
                id                           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                pengajuan_pkl_id             BIGINT UNSIGNED NULL,
                company_id                   BIGINT UNSIGNED NOT NULL,
                academic_period_id           BIGINT UNSIGNED NOT NULL,
                siswa_id                     BIGINT UNSIGNED NOT NULL,
                status                       ENUM(
                                                'draft','menunggu_verifikasi_kaprog','ditolak_kaprog',
                                                'menunggu_ttd_walas','ditolak_walas','menunggu_surat_pengantar',
                                                'surat_pengantar_terbit','menunggu_balasan_perusahaan',
                                                'rejected','sedang_pkl','withdrawn'
                                             ) NOT NULL DEFAULT 'draft',
                snapshot_company_json        JSON NULL,
                surat_balasan_path           VARCHAR(500) NULL,
                surat_balasan_hasil          ENUM('diterima','ditolak') NULL,
                surat_balasan_uploaded_at    TIMESTAMP NULL,
                accepted_by                  BIGINT UNSIGNED NULL,
                accepted_at                  TIMESTAMP NULL,
                is_provisional               BOOLEAN NOT NULL DEFAULT FALSE,
                dokumen_fisik_diterima_at    TIMESTAMP NULL,
                dokumen_fisik_confirmed_by   BIGINT UNSIGNED NULL,
                last_provisional_reminder_at DATE NULL,
                tanggal_mulai_pkl            DATE NULL,
                tanggal_selesai_pkl          DATE NULL,
                active_siswa_id              BIGINT UNSIGNED GENERATED ALWAYS AS (
                                                CASE WHEN status NOT IN ('rejected','withdrawn') THEN siswa_id ELSE NULL END
                                             ) STORED,
                withdraw_requested_at        TIMESTAMP NULL,
                withdraw_requested_by        BIGINT UNSIGNED NULL,
                withdraw_reason              TEXT NULL,
                withdraw_approved_by         BIGINT UNSIGNED NULL,
                withdrawn_at                 TIMESTAMP NULL,
                resubmitted_from_id          BIGINT UNSIGNED NULL,
                created_at                   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at                   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (pengajuan_pkl_id, company_id, academic_period_id)
                    REFERENCES pengajuan_pkl(id, company_id, academic_period_id),
                FOREIGN KEY (company_id) REFERENCES companies(id),
                FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id),
                FOREIGN KEY (siswa_id) REFERENCES users(id),
                FOREIGN KEY (accepted_by) REFERENCES users(id),
                FOREIGN KEY (dokumen_fisik_confirmed_by) REFERENCES users(id),
                FOREIGN KEY (withdraw_requested_by) REFERENCES users(id),
                FOREIGN KEY (withdraw_approved_by) REFERENCES users(id),
                FOREIGN KEY (resubmitted_from_id) REFERENCES pengajuan_siswa(id),
                UNIQUE KEY uq_active_student_period (active_siswa_id, academic_period_id),
                INDEX idx_siswa_status (siswa_id, status),
                INDEX idx_quota (company_id, academic_period_id, status),
                CONSTRAINT ck_provisional_status CHECK (is_provisional = FALSE OR status = 'sedang_pkl'),
                CONSTRAINT ck_snapshot_after_submit CHECK (status = 'draft' OR snapshot_company_json IS NOT NULL),
                CONSTRAINT ck_group_after_grouping CHECK (
                    pengajuan_pkl_id IS NOT NULL OR status IN (
                        'draft','menunggu_verifikasi_kaprog','ditolak_kaprog',
                        'menunggu_ttd_walas','ditolak_walas','menunggu_surat_pengantar')),
                CONSTRAINT ck_pkl_dates CHECK (
                    status <> 'sedang_pkl' OR (tanggal_mulai_pkl IS NOT NULL AND tanggal_selesai_pkl IS NOT NULL)),
                CONSTRAINT ck_pkl_dates_order CHECK (
                    tanggal_selesai_pkl IS NULL OR tanggal_mulai_pkl IS NULL OR tanggal_selesai_pkl >= tanggal_mulai_pkl),
                CONSTRAINT ck_withdraw_reason CHECK (withdraw_requested_at IS NULL OR withdraw_reason IS NOT NULL)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE guru_pembimbing_plotting (
                id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id          BIGINT UNSIGNED NOT NULL,
                academic_period_id  BIGINT UNSIGNED NOT NULL,
                guru_id             BIGINT UNSIGNED NOT NULL,
                plotted_by          BIGINT UNSIGNED NOT NULL,
                plotted_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES companies(id),
                FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id),
                FOREIGN KEY (guru_id) REFERENCES users(id),
                FOREIGN KEY (plotted_by) REFERENCES users(id),
                UNIQUE KEY uq_plotting (company_id, academic_period_id),
                INDEX idx_plotting_guru (guru_id, academic_period_id)
            ) ENGINE=InnoDB
        SQL);

        // `CREATE OR REPLACE` agar `migrate:fresh` tetap jalan (view tidak ikut ter-drop
        // oleh drop tabel).
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_kuota_perusahaan AS
            SELECT company_id, academic_period_id, COUNT(*) AS jumlah_siswa_accepted
            FROM pengajuan_siswa
            WHERE status = 'sedang_pkl'
            GROUP BY company_id, academic_period_id
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_kuota_perusahaan');
        DB::statement('DROP TABLE IF EXISTS guru_pembimbing_plotting');
        DB::statement('DROP TABLE IF EXISTS pengajuan_siswa');
        DB::statement('DROP TABLE IF EXISTS pengajuan_pkl');
    }
};
