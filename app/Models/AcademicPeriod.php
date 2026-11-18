<?php

namespace App\Models;

use App\Services\AcademicPeriodGuard;
use Database\Factories\AcademicPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'tahun_ajaran',
    'batch',
    'tanggal_mulai',
    'tanggal_selesai',
    'is_active',
    'locked_for_new_submission',
])]
class AcademicPeriod extends Model
{
    /** @use HasFactory<AcademicPeriodFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'academic_periods';

    /**
     * Cegah hapus dari jalur mana pun (UI, tinker, job) bila sudah ada data terkait.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $period): void {
            app(AcademicPeriodGuard::class)->pastikanBolehDihapus($period);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'is_active' => 'boolean',
            'locked_for_new_submission' => 'boolean',
        ];
    }
}
