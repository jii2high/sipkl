<?php

namespace App\Actions\Form;

use App\Actions\Action;
use App\Models\FormDefinition;
use App\Models\FormItem;
use App\Models\FormSection;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Ganti seluruh struktur Section → Items sebuah form (F8). Struktur terkunci
 * setelah ada submission (BR-F8-1), jadi penghapusan section/item terpakai
 * otomatis ditolak oleh pemeriksaan yang sama.
 */
final class SaveFormStructure extends Action
{
    public function handle(mixed ...$arguments): FormDefinition
    {
        [$definition, $sections, $kaprog] = $arguments;

        if (! $definition instanceof FormDefinition || ! $kaprog instanceof User) {
            throw new RuntimeException('Form dan Kaprog wajib diisi.');
        }

        Gate::forUser($kaprog)->authorize('update', $definition);

        $normalized = $this->normalize($sections);

        return $this->transaction(function () use ($definition, $normalized): FormDefinition {
            $locked = FormDefinition::query()->whereKey($definition->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isLocked()) {
                throw ValidationException::withMessages(['sections' => __('pkl.form.locked')]);
            }

            FormItem::query()
                ->whereIn('form_section_id', $locked->sections()->pluck('id'))
                ->delete();
            $locked->sections()->delete();

            foreach ($normalized as $index => $section) {
                $row = FormSection::query()->create([
                    'form_definition_id' => $locked->getKey(),
                    'label' => $section['label'],
                    'sort_order' => $index,
                ]);

                foreach ($section['items'] as $order => $item) {
                    FormItem::query()->create([
                        'form_section_id' => $row->getKey(),
                        'label' => $item['label'],
                        'has_alokasi_waktu' => $item['has_alokasi_waktu'],
                        'sort_order' => $order,
                    ]);
                }
            }

            $this->audit(
                $locked,
                'form_struktur_disimpan',
                null,
                'draft',
                ['jumlah_section' => count($normalized)],
                'Struktur form disimpan',
            );

            return $locked->refresh()->load('sections.items');
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, array{label: string, items: array<int, array{label: string, has_alokasi_waktu: bool}>}>
     */
    private function normalize(array $sections): array
    {
        if ($sections === []) {
            throw ValidationException::withMessages(['sections' => __('pkl.form.publish_empty')]);
        }

        $result = [];

        foreach ($sections as $section) {
            $label = trim((string) ($section['label'] ?? ''));

            if ($label === '') {
                throw ValidationException::withMessages(['sections' => __('pkl.form.section_label_required')]);
            }

            $items = [];

            foreach ((array) ($section['items'] ?? []) as $item) {
                $itemLabel = trim((string) ($item['label'] ?? ''));

                if ($itemLabel === '') {
                    throw ValidationException::withMessages(['sections' => __('pkl.form.item_label_required')]);
                }

                $items[] = [
                    'label' => $itemLabel,
                    'has_alokasi_waktu' => (bool) ($item['has_alokasi_waktu'] ?? false),
                ];
            }

            $result[] = ['label' => $label, 'items' => $items];
        }

        return $result;
    }
}
