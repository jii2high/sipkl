<?php

namespace App\Actions;

use App\Exceptions\InvalidTransition;
use App\Services\AuditService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Dasar semua Action domain (TRD.md §8 & §21). Tanggung jawab:
 * - menjalankan langkah dalam satu transaksi,
 * - mengubah status hanya bila status sumber masih sesuai (`UPDATE ... WHERE status = :dari`),
 * - idempoten: panggilan kedua tidak menambah efek,
 * - menulis audit event dalam transaksi yang sama.
 */
abstract class Action
{
    abstract public function handle(mixed ...$arguments): mixed;

    /**
     * Jalankan langkah aksi dalam satu transaksi. Perubahan status dan audit
     * selalu berada di transaksi yang sama.
     */
    protected function transaction(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }

    /**
     * Ubah status dengan syarat status sumber. Mengembalikan jumlah baris
     * terpengaruh; 0 berarti status sudah berubah (sudah diproses, idempoten).
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function transitionStatus(Model $model, string $to, ?string $from = null, array $attributes = []): int
    {
        $query = $model->newQuery()->whereKey($model->getKey());

        if ($from !== null) {
            $query->where('status', $from);
        }

        return $query->update($attributes + ['status' => $to]);
    }

    /** Status model sudah sama dengan yang dituju (hasil idempoten). */
    protected function alreadyInState(Model $model, string $status): bool
    {
        return $model->getAttribute('status') === $status;
    }

    protected function assertStatus(Model $model, string $expected): void
    {
        $current = (string) $model->getAttribute('status');

        if ($current !== $expected) {
            throw InvalidTransition::unexpected($current, $expected);
        }
    }

    /**
     * Tulis audit event pada transaksi yang sedang berjalan.
     *
     * @param  array<string, mixed>  $properties
     */
    protected function audit(
        Model $subject,
        string $event,
        ?string $from,
        string $to,
        array $properties = [],
        ?string $description = null,
    ): void {
        $class = $subject::class;

        app(AuditService::class)->log(
            description: $description ?? "Status {$class} berubah dari {$from} ke {$to}",
            subject: $subject,
            event: $event,
            properties: $properties + ['status_dari' => $from, 'status_ke' => $to],
        );
    }
}
