<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AkunExport implements FromArray, WithHeadings
{
    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Nama', 'NIS/NIP', 'Email', 'Role', 'Kelas/Jabatan', 'Jurusan'];
    }

    /** @return array<int, array<string, string>> */
    public function array(): array
    {
        $result = [];

        foreach (User::with('guruProfile', 'siswaProfile', 'roles')->cursor() as $user) {
            $nis = $user->siswaProfile?->nis;
            $nip = $user->guruProfile?->nip;
            $kelas = $user->siswaProfile?->kelas;
            $jabatan = $user->guruProfile?->jabatan;
            $jurusanSiswa = $user->siswaProfile?->kompetensiKeahlian?->nama;
            $jurusanGuru = $user->guruProfile?->kompetensiKeahlian?->nama;

            $result[] = [
                'nama' => $user->name,
                'nis_nip' => $nis ?? $nip ?? '-',
                'email' => $user->email,
                'role' => $user->getRoleNames()->first() ?? '-',
                'kelas_jabatan' => $kelas ?? $jabatan ?? '-',
                'jurusan' => $jurusanSiswa ?? $jurusanGuru ?? '-',
            ];
        }

        return $result;
    }
}
