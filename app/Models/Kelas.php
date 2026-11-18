<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['jurusan_id', 'nama', 'tingkat'])]
class Kelas extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'kelas';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['tingkat' => 'integer'];
    }
}
