<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table(name: 'guru_pembimbing_plotting', timestamps: false)]
#[Fillable(['company_id', 'academic_period_id', 'guru_id', 'plotted_by', 'plotted_at'])]
class GuruPembimbingPlotting extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['plotted_at' => 'datetime'];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<AcademicPeriod, $this> */
    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    /** @return BelongsTo<User, $this> */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /** @return HasMany<SuratTugasPembimbing, $this> */
    public function suratTugasHistory(): HasMany
    {
        return $this->hasMany(SuratTugasPembimbing::class, 'guru_pembimbing_plotting_id');
    }

    /** @return HasOne<SuratTugasPembimbing, $this> */
    public function suratTugas(): HasOne
    {
        return $this->hasOne(SuratTugasPembimbing::class, 'guru_pembimbing_plotting_id')
            ->whereNull('superseded_at');
    }
}
