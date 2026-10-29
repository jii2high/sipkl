-- ============================================================================
-- SCHEMA.SQL v2 — Sistem Informasi Manajemen PKL SMKN 1 Kota Bekasi
-- MySQL 8.0.16+ (CHECK constraint ditegakkan). Rancangan, BUKAN script migration:
-- terjemahkan ke migration Laravel per milestone; selaraskan dengan skema aktual.
--
-- Perubahan dari schema.sql v1 (lihat PRD.md Lampiran A, ID di kurung):
--   [A1] pengajuan_siswa: tambah company_id; pengajuan_pkl_id NULLABLE sampai grouping
--   [A2] companies: kolom signer NULLABLE + CHECK konsistensi (nama+email wajib bila verified)
--   [A3] pengajuan_pkl.status disederhanakan; 'guru_terplot' diturunkan dari plotting
--   [A4] counter nomor surat per tahun_ajaran (BUKAN per academic_period) + UNIQUE nomor
--   [A5] tanggal PKL per siswa (tanggal_mulai_pkl / tanggal_selesai_pkl)
--   [A6] signature_assets (scan TTD Kepsek) dirujuk oleh surat
--   [A8] FULLTEXT faqs dihapus (pencarian pakai LIKE)
--   [A10] surat balasan, approval withdrawal, penerimaan Kaprog kini punya kolom
--   [A11] user_roles: unique key anti-duplikat saat scope NULL; kelas.walas_id dihapus
--   [A12] visitasi_dokumentasi, document_qr_tokens ditambahkan
--   [A13] CHECK + UNIQUE tambahan untuk invarian (provisional, signature, submission)
-- Tabel framework Laravel (sessions, cache, cache_locks, jobs, job_batches,
-- failed_jobs, password_reset_tokens) dibuat artisan, tidak ditulis di sini.
-- Semua TIMESTAMP = UTC. Kolom DATE = tanggal kalender Asia/Jakarta (inklusif 23:59:59).
-- ============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- BAGIAN 1: MASTER — JURUSAN, KELAS, PERIODE
-- ============================================================================

CREATE TABLE jurusan (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode        VARCHAR(10)  NOT NULL UNIQUE,          -- 'RPL', 'TKR', 'TP'
    nama        VARCHAR(100) NOT NULL,
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Walas = baris user_roles(role='walas', kelas_id=...). TIDAK ada kelas.walas_id (satu sumber kebenaran).
CREATE TABLE kelas (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jurusan_id  BIGINT UNSIGNED NOT NULL,
    nama        VARCHAR(50) NOT NULL,                  -- 'XI RPL 1'
    tingkat     TINYINT NOT NULL,                      -- 11 atau 12
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (jurusan_id) REFERENCES jurusan(id),
    UNIQUE KEY uq_kelas_nama (jurusan_id, nama),
    CONSTRAINT ck_kelas_tingkat CHECK (tingkat IN (10, 11, 12))
) ENGINE=InnoDB;

-- academic_period = tahun ajaran + batch (PD-02). Boleh >1 aktif bersamaan.
CREATE TABLE academic_periods (
    id                        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tahun_ajaran              VARCHAR(9) NOT NULL,     -- '2026/2027'
    batch                     ENUM('1','2') NOT NULL,
    tanggal_mulai             DATE NOT NULL,           -- default tanggal PKL siswa
    tanggal_selesai           DATE NOT NULL,
    is_active                 BOOLEAN NOT NULL DEFAULT TRUE,
    locked_for_new_submission BOOLEAN NOT NULL DEFAULT FALSE,
    created_at                TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_period (tahun_ajaran, batch),
    CONSTRAINT ck_period_tahun CHECK (tahun_ajaran REGEXP '^[0-9]{4}/[0-9]{4}$'),
    CONSTRAINT ck_period_tanggal CHECK (tanggal_selesai >= tanggal_mulai)
) ENGINE=InnoDB;

-- ============================================================================
-- BAGIAN 2: USERS, MULTI-ROLE, OTP
-- ============================================================================

CREATE TABLE users (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nis_nip           VARCHAR(30)  NOT NULL UNIQUE,    -- password awal; BUKAN identifier login
    name              VARCHAR(150) NOT NULL,
    email             VARCHAR(150) NOT NULL UNIQUE,    -- identifier login
    email_verified_at TIMESTAMP NULL,                  -- diisi setelah OTP first-login benar
    password          VARCHAR(255) NOT NULL,           -- hash; awal = hash(nis_nip)
    user_type         ENUM('siswa','guru','hubin','kepsek') NOT NULL,
    jurusan_id        BIGINT UNSIGNED NULL,
    kelas_id          BIGINT UNSIGNED NULL,
    is_first_login    BOOLEAN NOT NULL DEFAULT TRUE,
    is_active         BOOLEAN NOT NULL DEFAULT TRUE,
    created_at        TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (jurusan_id) REFERENCES jurusan(id),
    FOREIGN KEY (kelas_id) REFERENCES kelas(id),
    CONSTRAINT ck_users_domain CHECK (email LIKE '%@smkn1kotabekasi.sch.id'),
    CONSTRAINT ck_users_siswa_scope CHECK (user_type <> 'siswa' OR (jurusan_id IS NOT NULL AND kelas_id IS NOT NULL))
) ENGINE=InnoDB;

-- 1 user boleh banyak role. Otorisasi memakai session active_role + baris ini.
-- jurusan_key/kelas_key membuat UNIQUE efektif walau scope NULL (MySQL menganggap NULL berbeda).
CREATE TABLE user_roles (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    role        ENUM('siswa','walas','guru_pembimbing','kaprog','hubin','kepsek') NOT NULL,
    jurusan_id  BIGINT UNSIGNED NULL,                  -- wajib untuk kaprog
    kelas_id    BIGINT UNSIGNED NULL,                  -- wajib untuk walas
    jurusan_key BIGINT UNSIGNED GENERATED ALWAYS AS (IFNULL(jurusan_id, 0)) STORED,
    kelas_key   BIGINT UNSIGNED GENERATED ALWAYS AS (IFNULL(kelas_id, 0)) STORED,
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (jurusan_id) REFERENCES jurusan(id),
    FOREIGN KEY (kelas_id) REFERENCES kelas(id),
    UNIQUE KEY uq_user_role_scope (user_id, role, jurusan_key, kelas_key),
    CONSTRAINT ck_role_scope CHECK (
        (role <> 'kaprog' OR jurusan_id IS NOT NULL)
        AND (role <> 'walas' OR kelas_id IS NOT NULL)
    )
) ENGINE=InnoDB;

-- 1 baris = 1 OTP yang dikirim. Batas resend (5x/30 menit, cooldown 30 menit) dihitung dari
-- COUNT baris per user dalam 30 menit terakhir + RateLimiter 'otp-resend' (terpisah dari 'otp-verify').
CREATE TABLE otp_codes (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        BIGINT UNSIGNED NOT NULL,
    code_hash      CHAR(64) NOT NULL,                  -- SHA-256, tidak pernah plaintext
    purpose        ENUM('first_login') NOT NULL DEFAULT 'first_login',
    attempt_count  TINYINT UNSIGNED NOT NULL DEFAULT 0, -- maks 3 verify
    expires_at     TIMESTAMP NOT NULL,                 -- created_at + 1 menit
    invalidated_at TIMESTAMP NULL,                     -- paksa invalid (attempt habis / OTP baru terbit)
    verified_at    TIMESTAMP NULL,
    created_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_otp_user_created (user_id, purpose, created_at)
) ENGINE=InnoDB;

-- ============================================================================
-- BAGIAN 3: PERUSAHAAN
-- ============================================================================

-- [A2] Siswa dapat membuat perusahaan baru; kontak signer dilengkapi Hubin (PD-16) sebelum
-- rantai TTD mencapai step 3/4. Guard step 3/4 di app: signer nama + email_verified_at wajib.
CREATE TABLE companies (
    id                                     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama                                   VARCHAR(200) NOT NULL,
    alamat                                 TEXT NOT NULL,
    email                                  VARCHAR(150) NULL,   -- strict unique (dinormalisasi lowercase di app)
    website                                VARCHAR(200) NULL,   -- strict unique (dinormalisasi di app)
    telp_fax                               VARCHAR(30) NULL,    -- strict unique (dinormalisasi digit di app)
    cp_name                                VARCHAR(150) NULL,
    cp_phone                               VARCHAR(30) NULL,    -- masked di UI sebelum disetujui Kaprog
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
    INDEX idx_company_nama (nama),                              -- bantu resolver fuzzy (kandidat awal)
    CONSTRAINT ck_company_not_self_merge CHECK (merged_into_id IS NULL OR merged_into_id <> id),
    CONSTRAINT ck_company_pi_signer CHECK (
        pembimbing_industri_email_verified_at IS NULL
        OR (pembimbing_industri_nama IS NOT NULL AND pembimbing_industri_email IS NOT NULL)),
    CONSTRAINT ck_company_ki_signer CHECK (
        kepala_industri_email_verified_at IS NULL
        OR (kepala_industri_nama IS NOT NULL AND kepala_industri_email IS NOT NULL))
) ENGINE=InnoDB;

-- [A6] Scan TTD Kepsek untuk surat (di-upload Hubin). Maks 1 aktif per pemilik.
CREATE TABLE signature_assets (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_user_id   BIGINT UNSIGNED NOT NULL,          -- Kepsek
    file_path       VARCHAR(500) NOT NULL,             -- private disk
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,
    active_owner_id BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN is_active THEN owner_user_id ELSE NULL END) STORED,
    uploaded_by     BIGINT UNSIGNED NOT NULL,          -- Hubin
    created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_user_id) REFERENCES users(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    UNIQUE KEY uq_signature_active_owner (active_owner_id)
) ENGINE=InnoDB;

-- ============================================================================
-- BAGIAN 4: PENGAJUAN PKL — COMPANY GROUP (pengajuan_pkl) & INDIVIDU (pengajuan_siswa)
-- ============================================================================

-- Company group = kumpulan siswa pada 1 perusahaan dalam 1 periode (BUKAN "batch", PD-02).
-- Satu company+periode boleh >1 group (siswa susulan). [A3] status disederhanakan:
--   terbentuk    = baru digrup, surat pengantar belum terbit
--   surat_terbit = Hubin sudah cetak/kirim surat pengantar
--   berjalan     = minimal 1 anggota berstatus sedang_pkl
--   bubar        = semua anggota rejected/withdrawn
-- 'guru_terplot' BUKAN status; turunan dari adanya guru_pembimbing_plotting(company, periode).
CREATE TABLE pengajuan_pkl (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id          BIGINT UNSIGNED NOT NULL,
    academic_period_id  BIGINT UNSIGNED NOT NULL,
    status              ENUM('terbentuk','surat_terbit','berjalan','bubar') NOT NULL DEFAULT 'terbentuk',
    -- Surat pengantar (counter jenis 'surat_pengantar', lihat nomor_surat_counters)
    tahun_ajaran        VARCHAR(9) NULL,
    nomor_urut          INT UNSIGNED NULL,
    nomor_surat         VARCHAR(100) NULL,
    signature_asset_id  BIGINT UNSIGNED NULL,
    file_path           VARCHAR(500) NULL,
    issued_by           BIGINT UNSIGNED NULL,          -- Hubin
    issued_at           TIMESTAMP NULL,
    sent_at             TIMESTAMP NULL,
    created_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id),
    FOREIGN KEY (signature_asset_id) REFERENCES signature_assets(id),
    FOREIGN KEY (issued_by) REFERENCES users(id),
    UNIQUE KEY uq_group_scope (id, company_id, academic_period_id),   -- target composite FK pengajuan_siswa
    UNIQUE KEY uq_group_nomor_urut (tahun_ajaran, nomor_urut),
    UNIQUE KEY uq_group_nomor_surat (tahun_ajaran, nomor_surat),
    INDEX idx_group_company_period (company_id, academic_period_id)
) ENGINE=InnoDB;

-- [A1] company_id dipilih siswa saat draft; pengajuan_pkl_id baru terisi setelah gate Walas (grouping).
-- Composite FK (pengajuan_pkl_id, company_id, academic_period_id) memastikan siswa, group, perusahaan,
-- dan periode konsisten. FK composite tidak ditegakkan saat pengajuan_pkl_id NULL (MATCH SIMPLE).
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
    snapshot_company_json        JSON NULL,            -- immutable; diisi saat submit (draft boleh NULL)
    -- Surat balasan perusahaan [A10]
    surat_balasan_path           VARCHAR(500) NULL,
    surat_balasan_hasil          ENUM('diterima','ditolak') NULL,
    surat_balasan_uploaded_at    TIMESTAMP NULL,
    -- Keputusan penerimaan Kaprog (PD-03)
    accepted_by                  BIGINT UNSIGNED NULL,
    accepted_at                  TIMESTAMP NULL,
    is_provisional               BOOLEAN NOT NULL DEFAULT FALSE,
    -- Konfirmasi dokumen fisik oleh Hubin (aksi terpisah; tidak mengubah keputusan Kaprog)
    dokumen_fisik_diterima_at    TIMESTAMP NULL,
    dokumen_fisik_confirmed_by   BIGINT UNSIGNED NULL,
    last_provisional_reminder_at DATE NULL,            -- tanggal Jakarta reminder terakhir (dedup harian)
    -- [A5] masa PKL per siswa; default disalin dari academic_periods saat Kaprog menerima
    tanggal_mulai_pkl            DATE NULL,
    tanggal_selesai_pkl          DATE NULL,
    -- PD-15: maks 1 pengajuan aktif per siswa per periode
    active_siswa_id              BIGINT UNSIGNED GENERATED ALWAYS AS (
                                    CASE WHEN status NOT IN ('rejected','withdrawn') THEN siswa_id ELSE NULL END
                                 ) STORED,
    -- Withdrawal (alasan wajib + approval Kaprog) [A10]
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
    -- Provisional hanya bermakna untuk sedang_pkl
    CONSTRAINT ck_provisional_status CHECK (is_provisional = FALSE OR status = 'sedang_pkl'),
    -- Snapshot wajib sejak submit
    CONSTRAINT ck_snapshot_after_submit CHECK (status = 'draft' OR snapshot_company_json IS NOT NULL),
    -- Group wajib terisi sejak surat pengantar terbit
    CONSTRAINT ck_group_after_grouping CHECK (
        pengajuan_pkl_id IS NOT NULL OR status IN (
            'draft','menunggu_verifikasi_kaprog','ditolak_kaprog',
            'menunggu_ttd_walas','ditolak_walas','menunggu_surat_pengantar')),
    -- Placement resmi wajib punya masa PKL
    CONSTRAINT ck_pkl_dates CHECK (
        status <> 'sedang_pkl' OR (tanggal_mulai_pkl IS NOT NULL AND tanggal_selesai_pkl IS NOT NULL)),
    CONSTRAINT ck_pkl_dates_order CHECK (
        tanggal_selesai_pkl IS NULL OR tanggal_mulai_pkl IS NULL OR tanggal_selesai_pkl >= tanggal_mulai_pkl),
    CONSTRAINT ck_withdraw_reason CHECK (withdraw_requested_at IS NULL OR withdraw_reason IS NOT NULL)
) ENGINE=InnoDB;

-- HANYA UNTUK BACA (PD-12). Jangan lock VIEW. Approval: lock row companies (SELECT ... FOR UPDATE),
-- hitung ulang status 'sedang_pkl' per company+periode di dalam transaksi; provisional ikut dihitung.
CREATE VIEW v_kuota_perusahaan AS
SELECT company_id, academic_period_id, COUNT(*) AS jumlah_siswa_accepted
FROM pengajuan_siswa
WHERE status = 'sedang_pkl'
GROUP BY company_id, academic_period_id;

-- ============================================================================
-- BAGIAN 5: PLOTTING GURU PEMBIMBING (by company + periode)
-- ============================================================================
-- Catatan: 1 company + 1 periode = 1 guru (PRD). Kasus perusahaan multi-jurusan: lihat PRD Lampiran B (Q-B).
CREATE TABLE guru_pembimbing_plotting (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id          BIGINT UNSIGNED NOT NULL,
    academic_period_id  BIGINT UNSIGNED NOT NULL,
    guru_id             BIGINT UNSIGNED NOT NULL,
    plotted_by          BIGINT UNSIGNED NOT NULL,      -- Kaprog
    plotted_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id),
    FOREIGN KEY (guru_id) REFERENCES users(id),
    FOREIGN KEY (plotted_by) REFERENCES users(id),
    UNIQUE KEY uq_plotting (company_id, academic_period_id),
    INDEX idx_plotting_guru (guru_id, academic_period_id)
) ENGINE=InnoDB;

-- ============================================================================
-- BAGIAN 6: PENOMORAN SURAT — counter per TAHUN AJARAN + jenis surat [A4]
-- ============================================================================
-- Reset tiap tahun ajaran (batch 1 & 2 berbagi counter). Ambil+naikkan dalam transaksi
-- (SELECT ... FOR UPDATE pada baris counter). Format string disusun di app:
-- [No Urut/Klasifikasi]/[Klasifikasi/No Urut]/SMKN.1-CADISDIK WIL 3
CREATE TABLE nomor_surat_counters (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tahun_ajaran  VARCHAR(9) NOT NULL,
    jenis_surat   ENUM('surat_pengantar','surat_tugas_pembimbing','surat_tugas_visitasi') NOT NULL,
    last_number   INT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_counter (tahun_ajaran, jenis_surat)
) ENGINE=InnoDB;

-- ============================================================================
-- BAGIAN 7: SURAT TUGAS (Pembimbing & Visitasi)
-- ============================================================================

CREATE TABLE surat_tugas_pembimbing (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    guru_pembimbing_plotting_id BIGINT UNSIGNED NOT NULL UNIQUE,   -- 1 plotting = 1 surat; periode lewat plotting
    tahun_ajaran                VARCHAR(9) NOT NULL,
    nomor_urut                  INT UNSIGNED NOT NULL,
    nomor_surat                 VARCHAR(100) NOT NULL,
    signature_asset_id          BIGINT UNSIGNED NOT NULL,          -- TTD Kepsek otomatis (AC-20)
    signed_at                   TIMESTAMP NOT NULL,
    file_path                   VARCHAR(500) NULL,
    created_at                  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guru_pembimbing_plotting_id) REFERENCES guru_pembimbing_plotting(id),
    FOREIGN KEY (signature_asset_id) REFERENCES signature_assets(id),
    UNIQUE KEY uq_stp_nomor_urut (tahun_ajaran, nomor_urut),
    UNIQUE KEY uq_stp_nomor_surat (tahun_ajaran, nomor_surat)
) ENGINE=InnoDB;

CREATE TABLE surat_tugas_visitasi (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    guru_id             BIGINT UNSIGNED NOT NULL,
    diajukan_oleh       ENUM('kaprog','hubin') NOT NULL,
    kaprog_id           BIGINT UNSIGNED NULL,
    is_general          BOOLEAN NOT NULL DEFAULT FALSE,    -- TRUE = semua perusahaan yang dipegang guru
    tahun_ajaran        VARCHAR(9) NULL,                   -- diisi saat disetujui
    nomor_urut          INT UNSIGNED NULL,
    nomor_surat         VARCHAR(100) NULL,
    tanggal_mulai       DATE NOT NULL,
    tanggal_selesai     DATE NOT NULL,                     -- default +7 hari; inklusif 23:59:59 Jakarta
    status              ENUM('diajukan','ditolak','disetujui','dicetak','aktif','kedaluwarsa') NOT NULL DEFAULT 'diajukan',
    approved_by         BIGINT UNSIGNED NULL,              -- Hubin
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
) ENGINE=InnoDB;

CREATE TABLE surat_tugas_visitasi_companies (
    id                       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    surat_tugas_visitasi_id  BIGINT UNSIGNED NOT NULL,
    company_id               BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (surat_tugas_visitasi_id) REFERENCES surat_tugas_visitasi(id),
    FOREIGN KEY (company_id) REFERENCES companies(id),
    UNIQUE KEY uq_stv_company (surat_tugas_visitasi_id, company_id)
) ENGINE=InnoDB;

-- [A12] Dokumentasi kunjungan (jpg/jpeg/png maks 5MB, private disk)
CREATE TABLE visitasi_dokumentasi (
    id                       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    surat_tugas_visitasi_id  BIGINT UNSIGNED NOT NULL,
    company_id               BIGINT UNSIGNED NOT NULL,
    uploaded_by              BIGINT UNSIGNED NOT NULL,     -- guru
    tanggal_kunjungan        DATE NOT NULL,
    file_path                VARCHAR(500) NOT NULL,
    keterangan               TEXT NULL,
    created_at               TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (surat_tugas_visitasi_id) REFERENCES surat_tugas_visitasi(id),
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_visitasi_dok (surat_tugas_visitasi_id, company_id)
) ENGINE=InnoDB;

-- ============================================================================
-- BAGIAN 8: BIMBINGAN LAPORAN
-- ============================================================================
-- GUARD app/backend: INSERT hanya jika pengajuan_siswa.status='sedang_pkl' AND is_provisional=FALSE.
CREATE TABLE bimbingan_submissions (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pengajuan_siswa_id  BIGINT UNSIGNED NOT NULL,
    file_path           VARCHAR(500) NOT NULL,             -- pdf/doc/docx maks 10MB
    keterangan          TEXT NULL,
    is_bagian_akhir     BOOLEAN NOT NULL DEFAULT FALSE,    -- boleh jika >=4 interaksi non-archived
    status              ENUM('menunggu_feedback','revisi','diterima') NOT NULL DEFAULT 'menunggu_feedback',
    feedback_note       TEXT NULL,
    reviewed_by         BIGINT UNSIGNED NULL,
    reviewed_at         TIMESTAMP NULL,
    is_archived         BOOLEAN NOT NULL DEFAULT FALSE,    -- TRUE saat withdrawal (tidak dihapus)
    archived_at         TIMESTAMP NULL,
    created_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pengajuan_siswa_id) REFERENCES pengajuan_siswa(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id),
    INDEX idx_bimbingan_siswa (pengajuan_siswa_id, is_archived)
) ENGINE=InnoDB;

-- ============================================================================
-- BAGIAN 9: LAPORAN & PENGESAHAN 5-TTD (sequential, partial-reject)
-- ============================================================================

CREATE TABLE laporan_versions (
    id                              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pengajuan_siswa_id              BIGINT UNSIGNED NOT NULL,
    version_number                  INT UNSIGNED NOT NULL,
    source_bimbingan_submission_id  BIGINT UNSIGNED NULL,  -- v1 = bagian akhir yang di-ACC
    file_path                       VARCHAR(500) NOT NULL,
    created_by                      BIGINT UNSIGNED NOT NULL,
    created_at                      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pengajuan_siswa_id) REFERENCES pengajuan_siswa(id),
    FOREIGN KEY (source_bimbingan_submission_id) REFERENCES bimbingan_submissions(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY uq_laporan_version (pengajuan_siswa_id, version_number)
) ENGINE=InnoDB;

-- Aturan: 1 baris per (versi, step). Baris dibuat 'pending' saat step sebelumnya signed, lalu berubah
-- SEKALI menjadi signed|rejected (tidak pernah kembali). Baris signed tidak pernah diubah/dihapus.
-- Setelah reject: laporan_versions baru dibuat; baris baru HANYA untuk step yang menolak dan sesudahnya.
-- Step sebelumnya tetap mengacu ke versi lama. Laporan legit = step 1..5 punya baris signed (versi mana pun).
CREATE TABLE document_signatures (
    id                        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pengajuan_siswa_id        BIGINT UNSIGNED NOT NULL,
    laporan_version_id        BIGINT UNSIGNED NOT NULL,
    step_order                TINYINT UNSIGNED NOT NULL,   -- 1 G.Pembimbing, 2 Kaprog, 3 P.Industri, 4 K.Industri, 5 Kepsek
    signer_type               ENUM('guru_pembimbing','kaprog','pembimbing_industri','kepala_industri','kepsek') NOT NULL,
    signer_user_id            BIGINT UNSIGNED NULL,        -- internal (identitas pencatat selalu tersimpan)
    signer_external_name      VARCHAR(150) NULL,           -- snapshot dari companies saat step dibuka
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
) ENGINE=InnoDB;

-- ============================================================================
-- BAGIAN 10: MAGIC LINKS & SESI EKSTERNAL
-- ============================================================================
-- token mentah = random kuat >=32 byte; simpan SHA-256 hex. Satu link aktif per (type, target).
-- reference_id (polimorfik):
--   pengesahan_p_industri / pengesahan_k_industri -> document_signatures.id
--   sidang_penguji                                -> jadwal_sidang.id
--   penilaian_industri                            -> form_submissions.id
--   revisi_nilai_sidang                           -> form_submissions.id
CREATE TABLE magic_links (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token_hash           CHAR(64) NOT NULL UNIQUE,
    type                 ENUM('pengesahan_p_industri','pengesahan_k_industri','sidang_penguji',
                              'penilaian_industri','revisi_nilai_sidang') NOT NULL,
    reference_id         BIGINT UNSIGNED NOT NULL,
    pin_hash             CHAR(64) NULL,                    -- sidang & revisi nilai (PIN 4 digit)
    pin_attempt_count    TINYINT UNSIGNED NOT NULL DEFAULT 0, -- maks 3
    device_fingerprint   VARCHAR(255) NULL,                -- hash(User-Agent + hash IP), binding longgar
    expires_at           TIMESTAMP NOT NULL,
    used_at              TIMESTAMP NULL,
    revoked_at           TIMESTAMP NULL,
    revoked_by           BIGINT UNSIGNED NULL,
    revoke_reason        TEXT NULL,                        -- wajib untuk override Hubin (PD-10)
    reissued_from_id     BIGINT UNSIGNED NULL,
    created_by           BIGINT UNSIGNED NULL,
    is_active            BOOLEAN NOT NULL DEFAULT TRUE,    -- FALSE saat consume/revoke/expired
    active_reference_id  BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN is_active THEN reference_id ELSE NULL END) STORED,
    created_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (revoked_by) REFERENCES users(id),
    FOREIGN KEY (reissued_from_id) REFERENCES magic_links(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_magic_type_ref (type, reference_id),
    UNIQUE KEY uq_magic_active_action_target (type, active_reference_id)
) ENGINE=InnoDB;

-- Sesi OTP email time-boxed (pengesahan & penilaian industri). Sesi TTL 24 jam setelah verifikasi.
CREATE TABLE external_sessions (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    magic_link_id       BIGINT UNSIGNED NOT NULL,
    otp_code_hash       CHAR(64) NOT NULL,
    otp_expires_at      TIMESTAMP NOT NULL,
    otp_attempt_count   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    verified_at         TIMESTAMP NULL,
    session_expires_at  TIMESTAMP NULL,                    -- verified_at + 24 jam (elapsed)
    revoked_at          TIMESTAMP NULL,
    created_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (magic_link_id) REFERENCES magic_links(id),
    INDEX idx_ext_session_link (magic_link_id, revoked_at)
) ENGINE=InnoDB;

-- ============================================================================
-- BAGIAN 11: SIDANG, FORM BUILDER (JSON hybrid), NILAI
-- ============================================================================

-- PD-16: nama + kontak email/WA aktif terverifikasi per penguji; NIK opsional.
CREATE TABLE external_examiners (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jurusan_id           BIGINT UNSIGNED NOT NULL,         -- scope Kaprog
    nama                 VARCHAR(150) NOT NULL,
    nik                  VARCHAR(30) NULL,
    contact_type         ENUM('email','whatsapp') NOT NULL,
    contact_value        VARCHAR(150) NOT NULL,
    contact_verified_at  TIMESTAMP NOT NULL,
    created_by           BIGINT UNSIGNED NOT NULL,
    created_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (jurusan_id) REFERENCES jurusan(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE jadwal_sidang (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pengajuan_siswa_id    BIGINT UNSIGNED NOT NULL UNIQUE,   -- reschedule = UPDATE, bukan baris baru
    external_examiner_id  BIGINT UNSIGNED NOT NULL,
    tanggal               DATE NOT NULL,
    urutan                INT UNSIGNED NOT NULL,             -- cascade shift dalam 1 transaksi
    status                ENUM('terjadwal','sesi_aktif','selesai') NOT NULL DEFAULT 'terjadwal',
    scheduled_by          BIGINT UNSIGNED NOT NULL,          -- Kaprog
    created_at            TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pengajuan_siswa_id) REFERENCES pengajuan_siswa(id),
    FOREIGN KEY (external_examiner_id) REFERENCES external_examiners(id),
    FOREIGN KEY (scheduled_by) REFERENCES users(id),
    INDEX idx_jadwal_tanggal_urutan (tanggal, urutan)
) ENGINE=InnoDB;

-- Struktur flatten 2 level: form_definitions -> form_sections -> form_items. Tanpa nested/EAV.
CREATE TABLE form_definitions (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jurusan_id          BIGINT UNSIGNED NOT NULL,
    academic_period_id  BIGINT UNSIGNED NOT NULL,
    form_type           ENUM('sidang','penilaian_industri') NOT NULL,
    nama                VARCHAR(150) NOT NULL,
    has_kehadiran       BOOLEAN NOT NULL DEFAULT FALSE,    -- Sakit/Izin/Tanpa Keterangan (free-input)
    has_catatan         BOOLEAN NOT NULL DEFAULT FALSE,
    is_published        BOOLEAN NOT NULL DEFAULT FALSE,    -- struktur terkunci setelah ada submission
    published_at        TIMESTAMP NULL,
    created_by          BIGINT UNSIGNED NOT NULL,
    created_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (jurusan_id) REFERENCES jurusan(id),
    FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY uq_form_definition_type (id, form_type)
) ENGINE=InnoDB;

CREATE TABLE form_sections (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_definition_id  BIGINT UNSIGNED NOT NULL,
    label               VARCHAR(150) NOT NULL,
    sort_order          INT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (form_definition_id) REFERENCES form_definitions(id),
    INDEX idx_section_form (form_definition_id, sort_order)
) ENGINE=InnoDB;

CREATE TABLE form_items (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_section_id    BIGINT UNSIGNED NOT NULL,
    label              VARCHAR(255) NOT NULL,
    has_alokasi_waktu  BOOLEAN NOT NULL DEFAULT FALSE,     -- untuk penilaian_industri
    sort_order         INT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (form_section_id) REFERENCES form_sections(id),
    INDEX idx_item_section (form_section_id, sort_order)
) ENGINE=InnoDB;

-- Jawaban (MySQL JSON): {"items":{"<form_item_id>":{"nilai":90,"alokasi_waktu":"20 jam"}},
--                        "kehadiran":{...},"catatan":"...","nilai_total":88.5}
-- sidang: 1 submission per jadwal; pengajuan_siswa_id wajib.
-- penilaian_industri: 1 submission per (company, periode, form); siswa hanya via form_submission_siswa;
--   pengajuan_siswa_id HARUS NULL (PD-14). [A13] company_id/academic_period_id mencegah duplikasi link.
-- is_submitted=TRUE => semua update ditolak server (403), termasuk dari sesi magic link yang sama.
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
                        ) STORED,                          -- app tidak boleh menulis JSON null; hilangkan key
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
) ENGINE=InnoDB;

CREATE TABLE form_submission_siswa (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_submission_id  BIGINT UNSIGNED NOT NULL,
    form_type           ENUM('sidang','penilaian_industri') NOT NULL DEFAULT 'penilaian_industri',
    pengajuan_siswa_id  BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (form_submission_id, form_type) REFERENCES form_submissions(id, form_type),
    FOREIGN KEY (pengajuan_siswa_id) REFERENCES pengajuan_siswa(id),
    UNIQUE KEY uq_submission_siswa (form_submission_id, pengajuan_siswa_id),
    CONSTRAINT ck_pivot_type CHECK (form_type = 'penilaian_industri')
) ENGINE=InnoDB;

-- Revisi nilai sidang: approver tunggal Kaprog; Kaprog TIDAK mengedit nilai. Penilaian industri tanpa revisi.
CREATE TABLE nilai_sidang_revisions (
    id                     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_submission_id     BIGINT UNSIGNED NOT NULL,
    jawaban_lama           JSON NOT NULL,
    jawaban_baru           JSON NULL,                      -- NULL sampai penguji submit ulang
    reported_by            ENUM('penguji','siswa') NOT NULL,
    reason                 TEXT NOT NULL,
    approved_by_kaprog_id  BIGINT UNSIGNED NOT NULL,
    unlock_magic_link_id   BIGINT UNSIGNED NOT NULL,
    unlock_expires_at      TIMESTAMP NOT NULL,             -- +24 jam elapsed
    status                 ENUM('menunggu_revisi','selesai','auto_relocked') NOT NULL DEFAULT 'menunggu_revisi',
    created_at             TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_submission_id) REFERENCES form_submissions(id),
    FOREIGN KEY (approved_by_kaprog_id) REFERENCES users(id),
    FOREIGN KEY (unlock_magic_link_id) REFERENCES magic_links(id),
    INDEX idx_revisi_status_expiry (status, unlock_expires_at)
) ENGINE=InnoDB;

-- ============================================================================
-- BAGIAN 12: VERIFIKASI QR DOKUMEN (/verify/{token}, payload minimal) [A12]
-- ============================================================================
CREATE TABLE document_qr_tokens (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token_hash     CHAR(64) NOT NULL UNIQUE,
    document_type  ENUM('surat_pengantar','surat_tugas_pembimbing','surat_tugas_visitasi') NOT NULL,
    document_id    BIGINT UNSIGNED NOT NULL,
    nomor_surat    VARCHAR(100) NOT NULL,                  -- hanya ini + status + tanggal yang tampil publik
    status         ENUM('valid','dicabut') NOT NULL DEFAULT 'valid',
    issued_at      TIMESTAMP NOT NULL,
    revoked_at     TIMESTAMP NULL,
    revoked_by     BIGINT UNSIGNED NULL,
    revoke_reason  TEXT NULL,
    FOREIGN KEY (revoked_by) REFERENCES users(id),
    UNIQUE KEY uq_qr_document (document_type, document_id)
) ENGINE=InnoDB;

-- ============================================================================
-- BAGIAN 13: AUDIT LOG, NOTIFIKASI
-- ============================================================================
-- Skema setara spatie/laravel-activitylog. Jangan simpan kredensial/OTP/PIN/token mentah di properties.
-- Aktor eksternal: causer NULL, isi properties.magic_link_id + properties.correlation_id.
CREATE TABLE activity_log (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    log_name      VARCHAR(100) NULL,
    description   TEXT NOT NULL,
    subject_type  VARCHAR(150) NULL,
    event         VARCHAR(100) NULL,
    subject_id    BIGINT UNSIGNED NULL,
    causer_type   VARCHAR(150) NULL,
    causer_id     BIGINT UNSIGNED NULL,
    properties    JSON NULL,
    batch_uuid    CHAR(36) NULL,
    created_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_subject (subject_type, subject_id),
    INDEX idx_causer (causer_type, causer_id),
    INDEX idx_log_name (log_name)
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

CREATE TABLE notification_delivery_failures (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    channel            ENUM('email','in_app','push') NOT NULL,
    recipient_email    VARCHAR(150) NULL,
    recipient_user_id  BIGINT UNSIGNED NULL,
    notification_type  VARCHAR(150) NOT NULL,
    related_type       VARCHAR(150) NULL,
    related_id         BIGINT UNSIGNED NULL,
    attempt_count      TINYINT UNSIGNED NOT NULL,
    failure_summary    TEXT NOT NULL,                      -- tanpa rahasia
    first_failed_at    TIMESTAMP NOT NULL,
    last_failed_at     TIMESTAMP NOT NULL,
    resolved_at        TIMESTAMP NULL,
    FOREIGN KEY (recipient_user_id) REFERENCES users(id),
    INDEX idx_notification_failure_monitor (resolved_at, last_failed_at)
) ENGINE=InnoDB;

-- ============================================================================
-- BAGIAN 14: FAQ (publik) — satu tabel kategori + satu tabel faqs (PD-11). Pencarian: LIKE. [A8]
-- ============================================================================
CREATE TABLE faq_categories (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(100) NOT NULL,
    sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE faqs (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    faq_category_id  BIGINT UNSIGNED NOT NULL,
    pertanyaan       VARCHAR(500) NOT NULL,
    jawaban          TEXT NOT NULL,
    sort_order       INT UNSIGNED NOT NULL DEFAULT 0,
    is_published     BOOLEAN NOT NULL DEFAULT TRUE,
    created_by       BIGINT UNSIGNED NOT NULL,             -- Hubin
    created_at       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (faq_category_id) REFERENCES faq_categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_faq_category_published (faq_category_id, is_published, sort_order)
) ENGINE=InnoDB;

-- ============================================================================
-- BAGIAN 15: RINGKASAN & EKSPOR AKREDITASI
-- ============================================================================
-- Di-sync oleh SyncSidangResultsSummaryJob saat form_submissions(sidang).is_submitted=TRUE
-- dan saat revisi nilai selesai. Hanya field PD-08 (tanpa telepon, alamat rumah, data orang tua).
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
) ENGINE=InnoDB;

-- Setiap attempt = 1 baris immutable. Retry = baris baru. Hanya status completed + tervalidasi yang
-- boleh diunduh (Hubin-only). Cleanup harian menghapus file + metadata setelah expires_at (30 hari).
CREATE TABLE accreditation_exports (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requested_by        BIGINT UNSIGNED NOT NULL,
    academic_period_id  BIGINT UNSIGNED NULL,
    retry_of_id         BIGINT UNSIGNED NULL,
    status              ENUM('queued','running','completed','failed') NOT NULL DEFAULT 'queued',
    snapshot_file_path  VARCHAR(500) NULL,                 -- private disk
    snapshot_checksum   CHAR(64) NULL,
    row_count           INT UNSIGNED NULL,
    failure_reason      TEXT NULL,
    requested_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at          TIMESTAMP NULL,
    completed_at        TIMESTAMP NULL,
    expires_at          TIMESTAMP NULL,                    -- completed_at + 30 hari
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id),
    FOREIGN KEY (retry_of_id) REFERENCES accreditation_exports(id),
    INDEX idx_accreditation_export_expiry (status, expires_at),
    CONSTRAINT ck_export_completed CHECK (
        status <> 'completed' OR (
            snapshot_file_path IS NOT NULL AND snapshot_checksum IS NOT NULL
            AND completed_at IS NOT NULL AND expires_at IS NOT NULL))
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
