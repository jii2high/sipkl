<?php

namespace App\Models;

use Database\Factories\PeriodePklFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodePkl extends Model
{
    /** @use HasFactory<PeriodePklFactory> */
    use HasFactory;

    protected $fillable = ['nama', 'tanggal_mulai', 'tanggal_selesai', 'status_aktif'];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'status_aktif' => 'boolean',
        ];
    }
}
