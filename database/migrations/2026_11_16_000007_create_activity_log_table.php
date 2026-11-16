<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * schema.sql Bagian 13 — activity_log (skema setara spatie/laravel-activitylog).
 *
 * Jangan pernah menulis kredensial/OTP/PIN/token mentah ke `properties`.
 * Aktor eksternal: causer NULL + properties.magic_link_id + properties.correlation_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name', 100)->nullable();
            $table->text('description');
            $table->string('subject_type', 150)->nullable();
            $table->string('event', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('causer_type', 150)->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->json('attribute_changes')->nullable();
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id'], 'idx_subject');
            $table->index(['causer_type', 'causer_id'], 'idx_causer');
            $table->index('log_name', 'idx_log_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
