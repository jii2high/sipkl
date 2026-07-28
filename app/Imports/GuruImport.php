<?php

namespace App\Imports;

use App\Models\GuruProfile;
use App\Models\KompetensiKeahlian;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class GuruImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    /** @var array<int, string> */
    public array $errors = [];

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'nip' => 'required|unique:guru_profiles,nip',
            'nama' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'jabatan' => 'required|in:kaprog,guru_pembimbing,hubin',
        ];
    }

    /** @param Collection<int, array<string, mixed>> $rows */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $user = User::create([
                'name' => $row['nama'],
                'email' => $row['email'],
                'password' => Hash::make($row['nip']),
                'must_change_password' => true,
            ]);

            $jurusanId = null;
            if (! empty($row['kompetensi_keahlian'])) {
                $jurusan = KompetensiKeahlian::where('nama', $row['kompetensi_keahlian'])->first();
                $jurusanId = $jurusan?->id;
                if (! $jurusan) {
                    $this->errors[] = "Jurusan {$row['kompetensi_keahlian']} tidak ditemukan untuk NIP {$row['nip']}";
                }
            }

            GuruProfile::create([
                'user_id' => $user->id,
                'nip' => $row['nip'],
                'jabatan' => $row['jabatan'],
                'kompetensi_keahlian_id' => $jurusanId,
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
