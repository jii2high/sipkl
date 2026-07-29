<?php

namespace App\Http\Requests\Hubin;

use App\Models\PeriodePkl;
use Illuminate\Foundation\Http\FormRequest;

class PeriodePklRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('kelola', PeriodePkl::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'status_aktif' => ['required', 'boolean'],
        ];
    }
}
