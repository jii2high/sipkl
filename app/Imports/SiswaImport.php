<?php

namespace App\Imports;

use App\Models\KompetensiKeahlian;
use App\Models\PeriodePkl;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class SiswaImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    /** @var array<int, string> */
    public array $errors = [];

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'nis' => 'required|unique:siswa_profiles,nis',
            'nama' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'periode' => 'required|exists:periode_pkls,nama',
            'kompetensi_keahlian' => 'required|exists:kompetensi_keahlian,nama',
        ];
    }

    /** @param Collection<int, array<string, mixed>> $rows */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $jurusan = KompetensiKeahlian::where('nama', $row['kompetensi_keahlian'])->first();
            $periode = PeriodePkl::where('nama', $row['periode'])->first();

            $user = User::create([
                'name' => $row['nama'],
                'email' => $row['email'],
                'password' => Hash::make($row['nis']),
                'must_change_password' => true,
            ]);
            $user->assignRole('siswa');

            SiswaProfile::create([
                'user_id' => $user->id,
                'nis' => $row['nis'],
                'periode_id' => $periode->id,
                'kompetensi_keahlian_id' => $jurusan->id,
                'alamat' => $row['alamat'] ?? null,
                'no_telp' => $row['no_telp'] ?? null,
            ]);
        }
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $f) {
            $this->errors[] = "Baris {$f->row()}: ".implode(', ', $f->errors());
        }
    }
}
