<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodePkl extends Model
{
    protected $table = 'periode_pkl';

    protected $fillable = [
        'nama',
        'tanggal_mulai',
        'tanggal_selesai',
        'status_aktif',
    ];
}
