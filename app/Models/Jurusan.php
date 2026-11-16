<?php

namespace App\Models;

use Database\Factories\JurusanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama'])]
class Jurusan extends Model
{
    /** @use HasFactory<JurusanFactory> */
    use HasFactory;

    protected $table = 'jurusan';

    /**
     * @return HasMany<Kelas, $this>
     */
    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
