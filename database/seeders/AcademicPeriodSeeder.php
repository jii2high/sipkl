<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use Illuminate\Database\Seeder;

class AcademicPeriodSeeder extends Seeder
{
    public function run(): void
    {
        AcademicPeriod::query()->firstOrCreate(
            ['tahun_ajaran' => '2025/2026', 'batch' => '1'],
            [
                'tanggal_mulai' => '2026-01-05',
                'tanggal_selesai' => '2026-04-30',
                'is_active' => true,
                'locked_for_new_submission' => false,
            ],
        );
    }
}
