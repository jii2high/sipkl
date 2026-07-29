<?php

namespace App\Imports;

use App\Models\KompetensiKeahlian;
use App\Models\SiswaProfile;
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

class SiswaImport implements OnEachRow, WithHeadingRow, SkipsOnFailure
{
    use Importable, SkipsFailures;

    public function onRow(Row $row): void
    {
        $data = array_map(static fn ($value) => is_string($value) ? trim($value) : $value, $row->toArray());
        $data['kompetensi_keahlian'] = isset($data['kompetensi_keahlian']) ? trim((string) $data['kompetensi_keahlian']) : null;
        $data['alamat'] = isset($data['alamat']) ? trim((string) $data['alamat']) : null;
        $data['no_telp'] = isset($data['no_telp']) ? trim((string) $data['no_telp']) : null;

        $validator = Validator::make($data, [
            'nama' => ['required', 'string', 'max:255'],
            'nis' => ['required', 'string', 'max:30', function ($attribute, $value, $fail) {
                if (SiswaProfile::where('nis', $value)->exists()) {
                    $fail('NIS sudah digunakan oleh akun siswa lain.');
                }
            }],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'kelas' => ['required', 'string', 'max:20'],
            'kompetensi_keahlian' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'no_telp' => ['nullable', 'string', 'max:20'],
        ]);

        $validator->after(function ($validator) use ($data) {
            $kompetensi = KompetensiKeahlian::where('nama', $data['kompetensi_keahlian'])
                ->orWhere('acronym', $data['kompetensi_keahlian'])
                ->first();

            if (!$kompetensi) {
                $validator->errors()->add('kompetensi_keahlian', 'Kompetensi keahlian tidak ditemukan.');
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

        $kompetensi = KompetensiKeahlian::where('nama', $data['kompetensi_keahlian'])
            ->orWhere('acronym', $data['kompetensi_keahlian'])
            ->first();

        $user = User::create([
            'name' => $data['nama'],
            'email' => $data['email'],
            'password' => Hash::make($data['nis']),
            'must_change_password' => true,
        ]);

        $user->assignRole('siswa');

        SiswaProfile::create([
            'user_id' => $user->id,
            'nis' => $data['nis'],
            'kelas' => $data['kelas'],
            'kompetensi_keahlian_id' => $kompetensi->id,
            'alamat' => $data['alamat'],
            'no_telp' => $data['no_telp'],
        ]);
    }
}
