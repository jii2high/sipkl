<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'role', 'jurusan_id', 'kelas_id'])]
class UserRole extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'user_roles';
}
