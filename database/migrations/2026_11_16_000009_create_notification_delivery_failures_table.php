<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * schema.sql Bagian 13 — notification_delivery_failures: kegagalan permanen
 * yang harus terlihat Hubin. Ringkasan kegagalan tidak boleh memuat rahasia.
 *
 * Catatan: schema.sql memakai TIMESTAMP NOT NULL; di sini dipetakan ke DATETIME
 * agar portabel di MySQL 8 & MariaDB (tanpa implicit default) dan tidak
 * dikonversi zona waktu server — nilai tetap UTC sesuai PRD §1.5 butir 9.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_delivery_failures', function (Blueprint $table) {
            $table->id();
            $table->enum('channel', ['email', 'in_app', 'push']);
            $table->string('recipient_email', 150)->nullable();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users');
            $table->string('notification_type', 150);
            $table->string('related_type', 150)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->unsignedTinyInteger('attempt_count');
            $table->text('failure_summary');
            $table->dateTime('first_failed_at');
            $table->dateTime('last_failed_at');
            $table->timestamp('resolved_at')->nullable();

            $table->index(['resolved_at', 'last_failed_at'], 'idx_notification_failure_monitor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_delivery_failures');
    }
};
