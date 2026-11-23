<?php

namespace App\Services;

use App\Models\FormDefinition;
use Illuminate\Validation\ValidationException;

/**
 * Validasi jawaban terhadap definisi form dan hitung `nilai_total` di server
 * (TRD.md §13.2, BR-F8-2). Rumus Q-E: rata-rata seluruh item, skala 0–100,
 * dibulatkan 2 desimal. Section Kehadiran dan Catatan tidak ikut perhitungan.
 */
final class ScoreService
{
    public const MAX_ITEM_SCORE = 100;

    /**
     * @param  array<string, mixed>  $input  `items` (id item => nilai), `kehadiran`, `catatan`
     * @return array<string, mixed> jawaban siap simpan ke kolom JSON `form_submissions.jawaban`
     */
    public function normalize(FormDefinition $definition, array $input): array
    {
        $sections = $definition->sections()->with('items')->get();

        $scores = [];
        $items = [];

        foreach ($sections as $section) {
            foreach ($section->items as $item) {
                $raw = data_get($input, 'items.'.$item->getKey());

                if ($raw === null || $raw === '') {
                    throw ValidationException::withMessages([
                        'jawaban' => __('pkl.form.item_required', ['label' => $item->label]),
                    ]);
                }

                if (! is_numeric($raw) || (float) $raw < 0 || (float) $raw > self::MAX_ITEM_SCORE) {
                    throw ValidationException::withMessages([
                        'jawaban' => __('pkl.form.item_range', [
                            'label' => $item->label,
                            'max' => self::MAX_ITEM_SCORE,
                        ]),
                    ]);
                }

                $scores[] = (float) $raw;

                $value = ['nilai' => round((float) $raw, 2)];

                // Alokasi waktu pada form industri hanya keterangan, tidak dinilai (Q-E).
                if ($item->has_alokasi_waktu) {
                    $alokasi = trim((string) data_get($input, 'alokasi.'.$item->getKey(), ''));
                    $value['alokasi_waktu'] = $alokasi === '' ? null : $alokasi;
                }

                $items[$item->getKey()] = $value;
            }
        }

        foreach ((array) data_get($input, 'items', []) as $id => $_) {
            if (! array_key_exists($id, $items)) {
                throw ValidationException::withMessages(['jawaban' => __('pkl.form.item_unknown')]);
            }
        }

        if ($scores === []) {
            throw ValidationException::withMessages(['jawaban' => __('pkl.form.no_items')]);
        }

        $jawaban = [
            'items' => $items,
            'nilai_total' => round(array_sum($scores) / count($scores), 2),
        ];

        if ($definition->has_kehadiran) {
            $jawaban['kehadiran'] = $this->kehadiran((array) ($input['kehadiran'] ?? []));
        }

        if ($definition->has_catatan) {
            $jawaban['catatan'] = trim((string) ($input['catatan'] ?? ''));
        }

        return $jawaban;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, int>
     */
    private function kehadiran(array $input): array
    {
        $result = [];

        foreach (['sakit', 'izin', 'tanpa_keterangan'] as $field) {
            $raw = $input[$field] ?? 0;

            if (! is_numeric($raw) || (int) $raw < 0) {
                throw ValidationException::withMessages(['jawaban' => __('pkl.form.kehadiran_invalid')]);
            }

            $result[$field] = (int) $raw;
        }

        return $result;
    }
}
