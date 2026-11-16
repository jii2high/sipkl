<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * schema.sql Bagian 2 — otp_codes. Kode disimpan sebagai SHA-256 (PRD §1.5 butir 6).
 *
 * `expires_at` dipetakan TIMESTAMP NOT NULL → DATETIME (lihat catatan di
 * migration notification_delivery_failures).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->char('code_hash', 64);
            $table->enum('purpose', ['first_login'])->default('first_login');
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->dateTime('expires_at');
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'purpose', 'created_at'], 'idx_otp_user_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
