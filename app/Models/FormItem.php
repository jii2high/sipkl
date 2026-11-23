<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['form_section_id', 'label', 'has_alokasi_waktu', 'sort_order'])]
class FormItem extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_alokasi_waktu' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<FormSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(FormSection::class, 'form_section_id');
    }
}
