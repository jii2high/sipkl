<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda pengingat H-7 penilaian industri (TRD.md §10). Baris submission baru
 * ada setelah tautan dibuat, jadi dedup "sekali per tanggal Jakarta" disimpan
 * pada perusahaan yang diingatkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->date('last_industry_reminder_at')->nullable()->after('pembimbing_industri_email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn('last_industry_reminder_at');
        });
    }
};
