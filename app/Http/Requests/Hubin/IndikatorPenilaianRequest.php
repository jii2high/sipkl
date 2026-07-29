<?php

namespace App\Http\Requests\Hubin;

use App\Models\IndikatorPenilaian;
use Illuminate\Foundation\Http\FormRequest;

class IndikatorPenilaianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('kelola', IndikatorPenilaian::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'kategori' => ['required', 'in:umum,teknis_kejuruan'],
            'kompetensi_keahlian_id' => ['nullable', 'exists:kompetensi_keahlian,id'],
            'nomor_urut' => ['required', 'integer', 'min:1', 'max:255'],
            'deskripsi' => ['required', 'string', 'max:255'],
        ];
    }
}
