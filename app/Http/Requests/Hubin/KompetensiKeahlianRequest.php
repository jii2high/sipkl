<?php

namespace App\Http\Requests\Hubin;

use App\Models\KompetensiKeahlian;
use Illuminate\Foundation\Http\FormRequest;

class KompetensiKeahlianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('kelola', KompetensiKeahlian::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'acronym' => ['required', 'string', 'max:20'],
        ];
    }
}
