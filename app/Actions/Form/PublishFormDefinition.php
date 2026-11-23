<?php

namespace App\Actions\Form;

use App\Actions\Action;
use App\Models\FormDefinition;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Publikasikan form (F8): ditolak bila tidak punya item (KP-F8-1). Idempoten.
 */
final class PublishFormDefinition extends Action
{
    public function handle(mixed ...$arguments): FormDefinition
    {
        [$definition, $kaprog] = $arguments;

        if (! $definition instanceof FormDefinition || ! $kaprog instanceof User) {
            throw new RuntimeException('Form dan Kaprog wajib diisi.');
        }

        Gate::forUser($kaprog)->authorize('publish', $definition);

        return $this->transaction(function () use ($definition): FormDefinition {
            $locked = FormDefinition::query()->whereKey($definition->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->is_published) {
                return $locked;
            }

            if ($locked->items()->count() === 0) {
                throw ValidationException::withMessages(['sections' => __('pkl.form.publish_empty')]);
            }

            $locked->update(['is_published' => true, 'published_at' => now()]);

            $this->audit(
                $locked,
                'form_dipublikasikan',
                'draft',
                'published',
                ['nama' => $locked->nama],
                'Form dipublikasikan',
            );

            return $locked->refresh();
        });
    }
}
