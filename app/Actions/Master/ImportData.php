<?php

namespace App\Actions\Master;

use App\Actions\Action;
use App\Enums\Role;
use App\Exceptions\ImportRowException;
use App\Models\Company;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AuditService;
use App\Services\CompanyResolver;
use App\Support\ImportReport;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Impor massal master data (T2.1/T2.2, KP-F2-2). Dua jenis yang dipakai M2:
 *
 * - `companies`: baris perusahaan (dipakai Hubin dan nanti form siswa).
 * - `accounts`: baris akun siswa/guru/walas/kaprog sekaligus perannya.
 *
 * Setiap baris diproses dalam transaksinya sendiri, jadi 5 baris salah dari 100
 * tetap menyisakan 95 baris masuk dan laporan 5 baris bermasalah. Perusahaan
 * memakai `CompanyResolver` yang sama dengan form supaya aturan duplikat identik
 * (BR-F2-2).
 */
final class ImportData extends Action
{
    public const KIND_COMPANIES = 'companies';

    public const KIND_ACCOUNTS = 'accounts';

    public function __construct(private readonly CompanyResolver $resolver) {}

    public function handle(mixed ...$arguments): ImportReport
    {
        [$kind, $rows, $actor] = $arguments + [null, null, null];

        $rows = is_iterable($rows) ? $rows : [];

        $total = 0;
        $imported = 0;
        $failed = [];

        foreach ($rows as $row) {
            $total++;

            try {
                $this->transaction(fn () => $this->importRow((string) $kind, (array) $row, $actor));
                $imported++;
            } catch (Throwable $exception) {
                $failed[] = ['row' => $total, 'messages' => $this->messages($exception)];
            }
        }

        $report = new ImportReport((string) $kind, $total, $imported, $failed);

        if ($actor instanceof User) {
            app(AuditService::class)->log(
                description: "Impor {$kind}: {$imported} dari {$total} baris masuk",
                event: 'imported',
                properties: [
                    'jenis' => $kind,
                    'total' => $total,
                    'masuk' => $imported,
                    'gagal' => $report->failedCount(),
                ],
                causer: $actor,
            );
        }

        return $report;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importRow(string $kind, array $row, mixed $actor): void
    {
        match ($kind) {
            self::KIND_COMPANIES => $this->importCompany($row, $actor),
            self::KIND_ACCOUNTS => $this->importAccount($row),
            default => throw ImportRowException::invalid("Jenis impor \"{$kind}\" tidak dikenal."),
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importCompany(array $row, mixed $actor): void
    {
        $nama = $this->text($row['nama'] ?? null);

        if ($nama === '') {
            throw ImportRowException::missing('nama');
        }

        $alamat = $this->text($row['alamat'] ?? null);

        if ($alamat === '') {
            throw ImportRowException::missing('alamat');
        }

        $check = $this->resolver->inspect($row);

        foreach (['email', 'website', 'telp_fax'] as $field) {
            if (isset($check->strict[$field])) {
                throw ImportRowException::invalid((string) $check->strictMessage($field));
            }
        }

        $keys = $this->resolver->keys($row);

        Company::query()->create([
            'nama' => $nama,
            'alamat' => $alamat,
            'email' => $keys['email'],
            'website' => $keys['website'],
            'telp_fax' => $keys['telp_fax'],
            'cp_name' => $this->text($row['cp_name'] ?? null) ?: null,
            'cp_phone' => $this->text($row['cp_phone'] ?? null) ?: null,
            'pembimbing_industri_nama' => $this->text($row['pembimbing_industri_nama'] ?? null) ?: null,
            'pembimbing_industri_email' => $this->text($row['pembimbing_industri_email'] ?? null) ?: null,
            'kepala_industri_nama' => $this->text($row['kepala_industri_nama'] ?? null) ?: null,
            'kepala_industri_email' => $this->text($row['kepala_industri_email'] ?? null) ?: null,
            'source' => 'import',
            'created_by' => $actor instanceof User ? $actor->getKey() : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importAccount(array $row): void
    {
        $nisNip = $this->text($row['nis_nip'] ?? null);

        if ($nisNip === '') {
            throw ImportRowException::missing('nis_nip');
        }

        $name = $this->text($row['name'] ?? $row['nama'] ?? null);

        if ($name === '') {
            throw ImportRowException::missing('name');
        }

        $roleValue = $this->text($row['role'] ?? null);
        $role = Role::tryFrom($roleValue);

        if ($role === null) {
            throw ImportRowException::unknown('Peran', $roleValue);
        }

        $email = $this->text($row['email'] ?? null)
            ?: $nisNip.'@'.config('pkl.school.email_domain');

        if (User::query()->where('nis_nip', $nisNip)->exists()) {
            throw ImportRowException::invalid("NIS/NIP \"{$nisNip}\" sudah terdaftar.");
        }

        if (User::query()->where('email', $email)->exists()) {
            throw ImportRowException::invalid("Email \"{$email}\" sudah terdaftar.");
        }

        $userType = $this->userType($role, $row['user_type'] ?? null);
        $jurusan = $this->jurusan($row['jurusan'] ?? null);
        $kelas = $this->kelas($row['kelas'] ?? null, $jurusan);

        $this->assertScope($role, $userType, $jurusan, $kelas);

        $user = User::query()->create([
            'nis_nip' => $nisNip,
            'name' => $name,
            'email' => $email,
            'password' => $nisNip,
            'user_type' => $userType,
            'jurusan_id' => $jurusan?->getKey(),
            'kelas_id' => $kelas?->getKey(),
            'is_first_login' => true,
            'is_active' => true,
        ]);

        UserRole::query()->create([
            'user_id' => $user->getKey(),
            'role' => $role->value,
            'jurusan_id' => $role === Role::Kaprog ? $jurusan?->getKey() : null,
            'kelas_id' => $role === Role::Walas ? $kelas?->getKey() : null,
        ]);
    }

    private function userType(Role $role, mixed $explicit): string
    {
        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        return match ($role) {
            Role::Siswa => 'siswa',
            Role::Hubin => 'hubin',
            Role::Kepsek => 'kepsek',
            default => 'guru',
        };
    }

    private function jurusan(mixed $value): ?Jurusan
    {
        $value = $this->text($value);

        if ($value === '') {
            return null;
        }

        return Jurusan::query()->where('kode', $value)->orWhere('nama', $value)->first()
            ?? throw ImportRowException::unknown('Jurusan', $value);
    }

    private function kelas(mixed $value, ?Jurusan $jurusan): ?Kelas
    {
        $value = $this->text($value);

        if ($value === '') {
            return null;
        }

        $query = Kelas::query()->where('nama', $value);

        if ($jurusan !== null) {
            $query->where('jurusan_id', $jurusan->getKey());
        }

        return $query->first() ?? throw ImportRowException::unknown('Kelas', $value);
    }

    private function assertScope(Role $role, string $userType, ?Jurusan $jurusan, ?Kelas $kelas): void
    {
        if ($role === Role::Siswa && ($jurusan === null || $kelas === null)) {
            throw ImportRowException::invalid('Siswa wajib punya jurusan dan kelas.');
        }

        if ($role === Role::Kaprog && $jurusan === null) {
            throw ImportRowException::invalid('Kaprog wajib punya jurusan.');
        }

        if ($role === Role::Walas && $kelas === null) {
            throw ImportRowException::invalid('Wali kelas wajib punya kelas.');
        }

        if ($userType === 'siswa' && ($jurusan === null || $kelas === null)) {
            throw ImportRowException::invalid('Akun siswa wajib punya jurusan dan kelas.');
        }
    }

    private function text(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    /**
     * @return array<int, string>
     */
    private function messages(Throwable $exception): array
    {
        if ($exception instanceof ValidationException) {
            return array_values($exception->errors())[0] ?? [$exception->getMessage()];
        }

        if ($exception instanceof QueryException) {
            return [__('pkl.import.row_rejected')];
        }

        return [$exception->getMessage()];
    }
}
