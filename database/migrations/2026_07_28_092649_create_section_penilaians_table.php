<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_penilaians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indikator_penilaian_id')->constrained('indikator_penilaians')->cascadeOnDelete();
            $table->unsignedTinyInteger('nomor_urut');
            $table->string('deskripsi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_penilaians');
    }
};
