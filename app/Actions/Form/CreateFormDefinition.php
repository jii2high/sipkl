<?php

namespace App\Actions\Form;

use App\Actions\Action;
use App\Models\AcademicPeriod;
use App\Models\FormDefinition;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Kaprog membuat definisi form untuk jurusannya sendiri (F8). Satu form = satu
 * pasangan (jurusan, periode, jenis); versi baru dibuat lewat DuplicateFormDefinition.
 */
final class CreateFormDefinition extends Action
{
    public function handle(mixed ...$arguments): FormDefinition
    {
        [$period, $type, $nama, $kaprog, $hasKehadiran, $hasCatatan] = $arguments + [null, null, null, null, false, false];

        if (! $period instanceof AcademicPeriod || ! $kaprog instanceof User) {
            throw new RuntimeException('Periode dan Kaprog wajib diisi.');
        }

        Gate::forUser($kaprog)->authorize('create', FormDefinition::class);

        if (! in_array($type, [FormDefinition::TYPE_SIDANG, FormDefinition::TYPE_INDUSTRI], true)) {
            throw ValidationException::withMessages(['formType' => __('pkl.form.type_invalid')]);
        }

        $nama = trim((string) $nama);

        if ($nama === '') {
            throw ValidationException::withMessages(['nama' => __('pkl.form.nama_required')]);
        }

        $jurusanId = $kaprog->roleScopeId('kaprog');

        if ($jurusanId === null) {
            throw ValidationException::withMessages(['periodId' => __('pkl.form.period_required')]);
        }

        return $this->transaction(function () use ($period, $type, $nama, $kaprog, $jurusanId, $hasKehadiran, $hasCatatan): FormDefinition {
            $definition = FormDefinition::query()->create([
                'jurusan_id' => $jurusanId,
                'academic_period_id' => $period->getKey(),
                'form_type' => $type,
                'nama' => $nama,
                'has_kehadiran' => (bool) $hasKehadiran,
                'has_catatan' => (bool) $hasCatatan,
                'created_by' => $kaprog->getKey(),
            ]);

            $this->audit(
                $definition,
                'form_dibuat',
                null,
                'draft',
                ['form_type' => $type, 'nama' => $nama],
                'Form dibuat',
            );

            return $definition;
        });
    }
}
