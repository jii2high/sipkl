<?php

namespace App\Imports;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Impor massal akun (TASKS M2.9).
 *
 * Kolom jurusan/kelas memakai kode atau nama yang mudah dibaca Hubin, bukan ID.
 * Akun baru memakai password awal NIS/NIP dan wajib melewati alur login awal,
 * sama seperti pembuatan akun lewat form.
 */
final class UsersImport extends BaseImport
{
    /**
     * @param  array<string, mixed>  $row
     */
    protected function prosesBaris(array $row, int $nomor): void
    {
        $nis = $this->ambil($row, 'nis_nip', 'nisnip', 'nis', 'nip');
        $name = $this->ambil($row, 'nama_lengkap', 'nama', 'name');
        $email = mb_strtolower((string) $this->ambil($row, 'email_sekolah', 'email'));
        $userType = mb_strtolower((string) ($this->ambil($row, 'tipe_pengguna', 'user_type') ?? 'siswa'));
        $namaJurusan = $this->ambil($row, 'jurusan');
        $namaKelas = $this->ambil($row, 'kelas');
        $isActive = $this->boolean($this->ambil($row, 'aktif', 'is_active'));

        $jurusan = $this->cariJurusan($namaJurusan);
        $kelas = $this->cariKelas($namaKelas, $jurusan);

        $errors = [];

        if ($namaJurusan !== null && $jurusan === null) {
            $errors['jurusan'] = 'Jurusan "'.$namaJurusan.'" tidak ditemukan.';
        }

        if ($namaKelas !== null && $kelas === null) {
            $errors['kelas'] = 'Kelas "'.$namaKelas.'" tidak ditemukan'.($jurusan !== null ? ' pada jurusan '.$jurusan->nama : '').'.';
        }

        if ($userType === 'siswa' && ($jurusan === null || $kelas === null)) {
            $errors['jurusan'] = $errors['jurusan'] ?? 'Siswa wajib punya jurusan dan kelas yang terdaftar.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        Validator::make(
            [
                'nis_nip' => $nis,
                'name' => $name,
                'email' => $email,
                'user_type' => $userType,
                'jurusan_id' => $jurusan?->getKey(),
                'kelas_id' => $kelas?->getKey(),
                'is_active' => $isActive,
            ],
            [
                'nis_nip' => ['required', 'string', 'max:30', 'unique:users,nis_nip'],
                'name' => ['required', 'string', 'max:150'],
                'email' => [
                    'required',
                    'email',
                    'max:150',
                    'unique:users,email',
                    'ends_with:@smkn1kotabekasi.sch.id',
                ],
                'user_type' => ['required', Rule::in(['siswa', 'guru', 'hubin', 'kepsek'])],
                'jurusan_id' => ['nullable', 'integer', 'exists:jurusan,id'],
                'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
                'is_active' => ['boolean'],
            ],
            [
                'nis_nip.required' => 'NIS/NIP wajib diisi.',
                'nis_nip.unique' => 'NIS/NIP sudah dipakai akun lain.',
                'name.required' => 'Nama lengkap wajib diisi.',
                'email.required' => 'Email sekolah wajib diisi.',
                'email.unique' => 'Email sekolah sudah dipakai akun lain.',
                'email.ends_with' => 'Email wajib memakai domain @smkn1kotabekasi.sch.id.',
                'user_type.in' => 'Tipe pengguna harus salah satu: siswa, guru, hubin, kepsek.',
            ],
            [
                'nis_nip' => 'NIS/NIP',
                'name' => 'nama lengkap',
                'email' => 'email sekolah',
            ],
        )->validate();

        User::query()->create([
            'nis_nip' => $nis,
            'name' => $name,
            'email' => $email,
            'password' => $nis,
            'user_type' => $userType,
            'jurusan_id' => $jurusan?->getKey(),
            'kelas_id' => $kelas?->getKey(),
            'is_first_login' => true,
            'is_active' => $isActive,
            'email_verified_at' => now(),
        ]);

        $this->report->sukses($nomor, $name.' ('.$nis.')');
    }

    private function cariJurusan(?string $nilai): ?Jurusan
    {
        if ($nilai === null) {
            return null;
        }

        $nilai = mb_strtolower($nilai);

        return Jurusan::query()
            ->whereRaw('LOWER(kode) = ?', [$nilai])
            ->orWhereRaw('LOWER(nama) = ?', [$nilai])
            ->first();
    }

    private function cariKelas(?string $nilai, ?Jurusan $jurusan): ?Kelas
    {
        if ($nilai === null) {
            return null;
        }

        return Kelas::query()
            ->whereRaw('LOWER(nama) = ?', [mb_strtolower($nilai)])
            ->when(
                $jurusan !== null,
                fn ($query) => $query->where('jurusan_id', $jurusan->getKey()),
            )
            ->first();
    }
}
