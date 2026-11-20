<?php

namespace App\Actions\Pengesahan;

use App\Actions\Action;
use App\Models\DocumentSignature;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Setujui banyak laporan sekaligus (BR-F7-1). Tiap laporan diproses independen:
 * satu gagal tidak membatalkan yang lain, dan hasil per item dikembalikan.
 */
final class BulkApproveSignatures extends Action
{
    /**
     * @param  array<int, int|string>  $signatureIds
     * @return array{approved: array<int, int>, failed: array<int, array{id: int, alasan: string}>}
     */
    public function handle(mixed ...$arguments): array
    {
        [$actor, $signatureIds] = $arguments;

        if (! $actor instanceof User || ! is_array($signatureIds)) {
            throw new \RuntimeException('Aktor dan daftar tanda tangan wajib diisi.');
        }

        $approved = [];
        $failed = [];

        foreach (array_unique(array_map('intval', $signatureIds)) as $id) {
            try {
                $signature = DocumentSignature::query()->find($id);

                if ($signature === null) {
                    throw ValidationException::withMessages(['step' => __('pkl.pengesahan.signature_not_found')]);
                }

                app(SignStep::class)->handle($signature, $actor);
                $approved[] = $id;
            } catch (ValidationException $exception) {
                $failed[] = ['id' => $id, 'alasan' => collect($exception->errors())->flatten()->first() ?? $exception->getMessage()];
            } catch (AuthorizationException) {
                $failed[] = ['id' => $id, 'alasan' => __('pkl.pengesahan.not_authorized')];
            } catch (\Throwable $exception) {
                $failed[] = ['id' => $id, 'alasan' => $exception->getMessage()];
            }
        }

        return ['approved' => $approved, 'failed' => $failed];
    }
}
