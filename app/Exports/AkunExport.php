<?php

namespace App\Exports;

use App\Models\GuruProfile;
use App\Models\SiswaProfile;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AkunExport implements FromCollection, WithHeadings
{
    public function collection(): SupportCollection
    {
        $rows = [];

        GuruProfile::with(['user', 'kompetensiKeahlian'])
            ->orderBy('jabatan')
            ->orderBy('nip')
            ->get()
            ->each(function (GuruProfile $profile) use (&$rows) {
                $rows[] = [
                    'tipe' => 'guru',
                    'nama' => $profile->user->name,
                    'email' => $profile->user->email,
                    'role' => $profile->jabatan,
                    'nip' => $profile->nip,
                    'nis' => null,
                    'kelas' => null,
                    'kompetensi_keahlian' => $profile->kompetensiKeahlian->nama ?? null,
                    'alamat' => null,
                    'no_telp' => null,
                ];
            });

        SiswaProfile::with(['user', 'kompetensiKeahlian'])
            ->orderBy('nis')
            ->get()
            ->each(function (SiswaProfile $profile) use (&$rows) {
                $rows[] = [
                    'tipe' => 'siswa',
                    'nama' => $profile->user->name,
                    'email' => $profile->user->email,
                    'role' => 'siswa',
                    'nip' => null,
                    'nis' => $profile->nis,
                    'kelas' => $profile->kelas,
                    'kompetensi_keahlian' => $profile->kompetensiKeahlian->nama ?? null,
                    'alamat' => $profile->alamat,
                    'no_telp' => $profile->no_telp,
                ];
            });

        return new SupportCollection($rows);
    }

    public function headings(): array
    {
        return [
            'tipe',
            'nama',
            'email',
            'role',
            'nip',
            'nis',
            'kelas',
            'kompetensi_keahlian',
            'alamat',
            'no_telp',
        ];
    }
}
