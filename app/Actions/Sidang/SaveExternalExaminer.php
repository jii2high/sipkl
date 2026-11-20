<?php

namespace App\Actions\Sidang;

use App\Actions\Action;
use App\Models\ExternalExaminer;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Kaprog menetapkan penguji sidang: nama + kontak email/WA yang sudah diverifikasi
 * (PD-16, F9 alur 1). `contact_verified_at` diisi saat penyimpanan karena Kaprog
 * hanya menambah penguji yang kontaknya sudah dipastikan aktif.
 */
final class SaveExternalExaminer extends Action
{
    public function handle(mixed ...$arguments): ExternalExaminer
    {
        [$nama, $contactType, $contactValue, $kaprog, $nik] = $arguments + [null, null, null, null, null];

        if (! $kaprog instanceof User) {
            throw new RuntimeException('Kaprog wajib diisi.');
        }

        Gate::forUser($kaprog)->authorize('create', ExternalExaminer::class);

        $nama = trim((string) $nama);
        $contactValue = trim((string) $contactValue);

        if ($nama === '') {
            throw ValidationException::withMessages(['nama' => __('pkl.sidang.examiner_nama_required')]);
        }

        if (! in_array($contactType, ['email', 'whatsapp'], true)) {
            throw ValidationException::withMessages(['contactType' => __('pkl.sidang.examiner_contact_type')]);
        }

        if ($contactValue === '') {
            throw ValidationException::withMessages(['contactValue' => __('pkl.sidang.examiner_contact_required')]);
        }

        if ($contactType === 'email' && filter_var($contactValue, FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationException::withMessages(['contactValue' => __('pkl.sidang.examiner_email_invalid')]);
        }

        $jurusanId = $kaprog->roleScopeId('kaprog');

        if ($jurusanId === null) {
            throw ValidationException::withMessages(['nama' => __('pkl.sidang.examiner_scope')]);
        }

        return $this->transaction(function () use ($nama, $contactType, $contactValue, $kaprog, $jurusanId, $nik): ExternalExaminer {
            $examiner = ExternalExaminer::query()->create([
                'jurusan_id' => $jurusanId,
                'nama' => $nama,
                'nik' => $nik === null || trim((string) $nik) === '' ? null : trim((string) $nik),
                'contact_type' => $contactType,
                'contact_value' => $contactValue,
                'contact_verified_at' => now(),
                'created_by' => $kaprog->getKey(),
            ]);

            $this->audit(
                $examiner,
                'penguji_ditetapkan',
                null,
                (string) $examiner->getKey(),
                ['nama' => $nama, 'contact_type' => $contactType],
                'Penguji sidang ditetapkan',
            );

            return $examiner;
        });
    }
}
