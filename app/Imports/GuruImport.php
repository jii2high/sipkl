<?php

namespace App\Imports;

use App\Models\GuruProfile;
use App\Models\KompetensiKeahlian;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Validators\Failure;

class GuruImport implements OnEachRow, WithHeadingRow, SkipsOnFailure
{
    use Importable, SkipsFailures;

    public function onRow(Row $row): void
    {
        $data = array_map(static fn ($value) => is_string($value) ? trim($value) : $value, $row->toArray());
        $data['jabatan'] = isset($data['jabatan']) ? strtolower((string) $data['jabatan']) : null;
        $data['kompetensi_keahlian'] = isset($data['kompetensi_keahlian']) ? trim((string) $data['kompetensi_keahlian']) : null;

        $validator = Validator::make($data, [
            'nama' => ['required', 'string', 'max:255'],
            'nip' => ['required', 'string', 'max:30', function ($attribute, $value, $fail) {
                if (GuruProfile::where('nip', $value)->exists()) {
                    $fail('NIP sudah digunakan oleh akun guru lain.');
                }
            }],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'jabatan' => ['required', Rule::in(['kaprog', 'guru_pembimbing', 'hubin'])],
            'kompetensi_keahlian' => ['nullable', 'string', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($data) {
            if ($data['jabatan'] === 'kaprog' && empty($data['kompetensi_keahlian'])) {
                $validator->errors()->add('kompetensi_keahlian', 'Kompetensi keahlian wajib untuk jabatan kaprog.');
            }

            if (!empty($data['kompetensi_keahlian'])) {
                $kompetensi = KompetensiKeahlian::where('nama', $data['kompetensi_keahlian'])
                    ->orWhere('acronym', $data['kompetensi_keahlian'])
                    ->first();

                if (!$kompetensi) {
                    $validator->errors()->add('kompetensi_keahlian', 'Kompetensi keahlian tidak ditemukan.');
                }
            }
        });

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $attribute => $messages) {
                foreach ($messages as $message) {
                    $this->onFailure(new Failure($row->getIndex(), $attribute, [$message], $data));
                }
            }

            return;
        }

        $kompetensi = null;
        if (!empty($data['kompetensi_keahlian'])) {
            $kompetensi = KompetensiKeahlian::where('nama', $data['kompetensi_keahlian'])
                ->orWhere('acronym', $data['kompetensi_keahlian'])
                ->first();
        }

        $user = User::create([
            'name' => $data['nama'],
            'email' => $data['email'],
            'password' => Hash::make($data['nip']),
            'must_change_password' => true,
        ]);

        GuruProfile::create([
            'user_id' => $user->id,
            'nip' => $data['nip'],
            'jabatan' => $data['jabatan'],
            'kompetensi_keahlian_id' => $kompetensi?->id,
        ]);
    }
}
