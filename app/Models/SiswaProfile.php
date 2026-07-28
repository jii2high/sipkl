<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiswaProfile extends Model
{
    protected $table = 'siswa_profiles';

    protected $fillable = ['user_id', 'nis', 'kelas', 'kompetensi_keahlian_id', 'alamat', 'no_telp'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kompetensiKeahlian()
    {
        return $this->belongsTo(KompetensiKeahlian::class);
    }
}
