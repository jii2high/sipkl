<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Draft → terbit: nomor_surat & tanggal_terbit dibuat saat terbit (BR18)
        Schema::table('surat_tugas_monitoring', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
            $table->string('nomor_surat')->nullable()->change();
            $table->foreignId('diterbitkan_by')->nullable()->change();
            $table->date('tanggal_terbit')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('surat_tugas_monitoring', function (Blueprint $table) {
            $table->string('nomor_surat')->nullable(false)->change();
            $table->foreignId('diterbitkan_by')->nullable(false)->change();
            $table->date('tanggal_terbit')->nullable(false)->change();
            $table->string('status')->default('aktif')->change();
        });
    }
};
