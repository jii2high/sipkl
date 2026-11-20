<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Portal penguji masuk dengan PIN 4 digit, bukan OTP email (F9 alur 2). Karena
 * itu sesi turunan tautan penguji tidak punya kode OTP, sehingga dua kolom OTP
 * harus boleh NULL. `ExternalOtpService` tetap mengisinya untuk portal signer.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE external_sessions
                MODIFY otp_code_hash CHAR(64) NULL,
                MODIFY otp_expires_at TIMESTAMP NULL
        SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE external_sessions
                MODIFY otp_code_hash CHAR(64) NOT NULL,
                MODIFY otp_expires_at TIMESTAMP NOT NULL
        SQL);
    }
};
