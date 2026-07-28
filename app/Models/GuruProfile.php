<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuruProfile extends Model
{
    protected $table = 'guru_profiles';

    protected $fillable = ['user_id', 'nip', 'jabatan', 'kompetensi_keahlian_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kompetensiKeahlian()
    {
        return $this->belongsTo(KompetensiKeahlian::class);
    }
}
