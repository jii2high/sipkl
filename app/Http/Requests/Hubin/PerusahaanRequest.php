<?php

namespace App\Http\Requests\Hubin;

use App\Models\Perusahaan;
use Illuminate\Foundation\Http\FormRequest;

class PerusahaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('kelola', Perusahaan::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'telp' => ['nullable', 'string', 'max:50'],
            'kuota_saran' => ['required', 'integer', 'min:0', 'max:255'],
        ];
    }
}
