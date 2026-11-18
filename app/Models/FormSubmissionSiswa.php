<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'form_submission_siswa', timestamps: false)]
#[Fillable(['form_submission_id', 'form_type', 'pengajuan_siswa_id'])]
class FormSubmissionSiswa extends Model {}
