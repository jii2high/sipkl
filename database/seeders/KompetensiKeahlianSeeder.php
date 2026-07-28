<?php

namespace Database\Seeders;

use App\Models\KompetensiKeahlian;
use Illuminate\Database\Seeder;

class KompetensiKeahlianSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['nama' => 'Rekayasa Perangkat Lunak', 'acronym' => 'RPL'],
            ['nama' => 'Teknik Komputer dan Jaringan', 'acronym' => 'TKJ'],
            ['nama' => 'Multimedia', 'acronym' => 'MM'],
            ['nama' => 'Akuntansi dan Keuangan Lembaga', 'acronym' => 'AKL'],
        ])->each(fn ($item) => KompetensiKeahlian::create($item));
    }
}
