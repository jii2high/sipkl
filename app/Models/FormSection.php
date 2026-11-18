<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['form_definition_id', 'label', 'sort_order'])]
class FormSection extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
