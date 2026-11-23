<?php

namespace App\Actions\Form;

use App\Actions\Action;
use App\Models\FormDefinition;
use App\Models\FormItem;
use App\Models\FormSection;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

/**
 * Pengganti struktur form yang sudah terkunci: menyalin definisi beserta section
 * dan item sebagai versi baru yang belum dipublikasikan (KP-F8-2).
 */
final class DuplicateFormDefinition extends Action
{
    public function handle(mixed ...$arguments): FormDefinition
    {
        [$definition, $kaprog] = $arguments;

        if (! $definition instanceof FormDefinition || ! $kaprog instanceof User) {
            throw new RuntimeException('Form dan Kaprog wajib diisi.');
        }

        Gate::forUser($kaprog)->authorize('update', $definition);

        return $this->transaction(function () use ($definition, $kaprog): FormDefinition {
            $source = FormDefinition::query()->whereKey($definition->getKey())->lockForUpdate()->firstOrFail();

            $copy = FormDefinition::query()->create([
                'jurusan_id' => $source->jurusan_id,
                'academic_period_id' => $source->academic_period_id,
                'form_type' => $source->form_type,
                'nama' => $source->nama,
                'has_kehadiran' => $source->has_kehadiran,
                'has_catatan' => $source->has_catatan,
                'created_by' => $kaprog->getKey(),
            ]);

            foreach ($source->sections()->with('items')->get() as $section) {
                $row = FormSection::query()->create([
                    'form_definition_id' => $copy->getKey(),
                    'label' => $section->label,
                    'sort_order' => $section->sort_order,
                ]);

                foreach ($section->items as $item) {
                    FormItem::query()->create([
                        'form_section_id' => $row->getKey(),
                        'label' => $item->label,
                        'has_alokasi_waktu' => $item->has_alokasi_waktu,
                        'sort_order' => $item->sort_order,
                    ]);
                }
            }

            $this->audit(
                $copy,
                'form_versi_baru',
                null,
                'draft',
                ['sumber_id' => $source->getKey(), 'nama' => $copy->nama],
                'Versi baru form dibuat',
            );

            return $copy->refresh()->load('sections.items');
        });
    }
}
