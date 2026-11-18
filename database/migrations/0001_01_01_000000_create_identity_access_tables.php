<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kelompok "Identitas & akses" (TRD.md §6.1): jurusan, kelas, users, user_roles,
 * otp_codes, sesi. DDL mengikuti docs/schema.sql apa adanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE jurusan (
                id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                kode        VARCHAR(10)  NOT NULL UNIQUE,
                nama        VARCHAR(100) NOT NULL,
                created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE kelas (
                id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                jurusan_id  BIGINT UNSIGNED NOT NULL,
                nama        VARCHAR(50) NOT NULL,
                tingkat     TINYINT NOT NULL,
                created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (jurusan_id) REFERENCES jurusan(id),
                UNIQUE KEY uq_kelas_nama (jurusan_id, nama),
                CONSTRAINT ck_kelas_tingkat CHECK (tingkat IN (10, 11, 12))
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE users (
                id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                nis_nip           VARCHAR(30)  NOT NULL UNIQUE,
                name              VARCHAR(150) NOT NULL,
                email             VARCHAR(150) NOT NULL UNIQUE,
                email_verified_at TIMESTAMP NULL,
                password          VARCHAR(255) NOT NULL,
                user_type         ENUM('siswa','guru','hubin','kepsek') NOT NULL,
                jurusan_id        BIGINT UNSIGNED NULL,
                kelas_id          BIGINT UNSIGNED NULL,
                is_first_login    BOOLEAN NOT NULL DEFAULT TRUE,
                is_active         BOOLEAN NOT NULL DEFAULT TRUE,
                theme_preference  ENUM('light','dark','system') NULL,
                created_at        TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at        TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (jurusan_id) REFERENCES jurusan(id),
                FOREIGN KEY (kelas_id) REFERENCES kelas(id),
                CONSTRAINT ck_users_domain CHECK (email LIKE '%@smkn1kotabekasi.sch.id'),
                CONSTRAINT ck_users_siswa_scope CHECK (user_type <> 'siswa' OR (jurusan_id IS NOT NULL AND kelas_id IS NOT NULL))
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE user_roles (
                id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id     BIGINT UNSIGNED NOT NULL,
                role        ENUM('siswa','walas','guru_pembimbing','kaprog','hubin','kepsek') NOT NULL,
                jurusan_id  BIGINT UNSIGNED NULL,
                kelas_id    BIGINT UNSIGNED NULL,
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
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE otp_codes (
                id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id        BIGINT UNSIGNED NOT NULL,
                code_hash      CHAR(64) NOT NULL,
                purpose        ENUM('first_login') NOT NULL DEFAULT 'first_login',
                attempt_count  TINYINT UNSIGNED NOT NULL DEFAULT 0,
                expires_at     TIMESTAMP NOT NULL,
                invalidated_at TIMESTAMP NULL,
                verified_at    TIMESTAMP NULL,
                created_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                INDEX idx_otp_user_created (user_id, purpose, created_at)
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE password_reset_tokens (
                email      VARCHAR(150) NOT NULL PRIMARY KEY,
                token      VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NULL
            ) ENGINE=InnoDB
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE sessions (
                id            VARCHAR(255) NOT NULL PRIMARY KEY,
                user_id       BIGINT UNSIGNED NULL,
                ip_address    VARCHAR(45) NULL,
                user_agent    TEXT NULL,
                payload       LONGTEXT NOT NULL,
                last_activity INT NOT NULL,
                INDEX sessions_user_id_index (user_id),
                INDEX sessions_last_activity_index (last_activity)
            ) ENGINE=InnoDB
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS sessions');
        DB::statement('DROP TABLE IF EXISTS password_reset_tokens');
        DB::statement('DROP TABLE IF EXISTS otp_codes');
        DB::statement('DROP TABLE IF EXISTS user_roles');
        DB::statement('DROP TABLE IF EXISTS users');
        DB::statement('DROP TABLE IF EXISTS kelas');
        DB::statement('DROP TABLE IF EXISTS jurusan');
    }
};
