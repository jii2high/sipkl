<?php

namespace App\Http\Requests\Hubin;

use App\Models\SectionPenilaian;
use Illuminate\Foundation\Http\FormRequest;

class SectionPenilaianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('kelola', SectionPenilaian::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'indikator_penilaian_id' => ['required', 'exists:indikator_penilaian,id'],
            'nomor_urut' => ['required', 'integer', 'min:1', 'max:255'],
            'deskripsi' => ['required', 'string', 'max:255'],
        ];
    }
}
