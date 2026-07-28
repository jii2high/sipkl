<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indikator_penilaians', function (Blueprint $table) {
            $table->id();
            $table->enum('kategori', ['umum', 'teknis_kejuruan']);
            $table->foreignId('kompetensi_keahlian_id')->nullable()->constrained('kompetensi_keahlian');
            $table->unsignedTinyInteger('nomor_urut');
            $table->string('deskripsi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indikator_penilaians');
    }
};
