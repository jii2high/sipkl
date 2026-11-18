<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * schema.sql Bagian 3 — companies.
 *
 * Signer Pembimbing/Kepala Industri NULLABLE (PRD Lampiran A2): perusahaan baru
 * buatan siswa boleh belum punya signer; guard dicek saat rantai TTD mencapai
 * step 3/4. Normalisasi email/website/telp dilakukan di app (M2.2).
 *
 * Catatan: aturan "perusahaan tidak boleh merge ke dirinya sendiri" TIDAK bisa
 * dijadikan CHECK karena MySQL 8 maupun MariaDB melarang CHECK yang mengacu ke
 * kolom AUTO_INCREMENT (`id`). Aturan itu ditegakkan di MergeCompanyAction (M2.5)
 * dan diverifikasi lewat test.
 *
 * `*_email_verified_at` memakai DATETIME (lihat catatan migration 2026_11_16_000009).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 200);
            $table->text('alamat');
            $table->string('email', 150)->nullable()->unique('uq_company_email');
            $table->string('website', 200)->nullable()->unique('uq_company_website');
            $table->string('telp_fax', 30)->nullable()->unique('uq_company_telp_fax');
            $table->string('cp_name', 150)->nullable();
            $table->string('cp_phone', 30)->nullable();
            $table->string('pembimbing_industri_nama', 150)->nullable();
            $table->string('pembimbing_industri_email', 150)->nullable();
            $table->dateTime('pembimbing_industri_email_verified_at')->nullable();
            $table->string('kepala_industri_nama', 150)->nullable();
            $table->string('kepala_industri_email', 150)->nullable();
            $table->dateTime('kepala_industri_email_verified_at')->nullable();
            $table->enum('source', ['siswa', 'hubin', 'import'])->default('hubin');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('merged_into_id')->nullable()->constrained('companies');
            $table->timestamps();

            $table->index('nama', 'idx_company_nama');
        });

        DB::statement(
            'ALTER TABLE companies ADD CONSTRAINT ck_company_pi_signer CHECK ('
            .'pembimbing_industri_email_verified_at IS NULL '
            .'OR (pembimbing_industri_nama IS NOT NULL AND pembimbing_industri_email IS NOT NULL))'
        );
        DB::statement(
            'ALTER TABLE companies ADD CONSTRAINT ck_company_ki_signer CHECK ('
            .'kepala_industri_email_verified_at IS NULL '
            .'OR (kepala_industri_nama IS NOT NULL AND kepala_industri_email IS NOT NULL))'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
