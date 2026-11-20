<?php

namespace App\Actions\Sidang;

use App\Actions\Action;
use App\Models\JadwalSidang;
use App\Models\User;
use App\Support\JakartaTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Cascade shift jadwal sidang (F9 alur 4, TRD.md §10): Kaprog memindahkan atau
 * menghapus satu jadwal, dan sisa antrean pada tanggal asal bergeser ke hari kerja
 * berikutnya. Seluruh susunan baru divalidasi lebih dulu; bila ada konflik, tidak
 * satu pun baris berubah dan daftar konflik dikembalikan (KP-F9-2).
 *
 * Konflik yang diperiksa: urutan bentrok di tanggal sama, penguji dobel pada
 * (tanggal, urutan) sama, jadwal melewati tanggal selesai PKL siswa, siswa tanpa
 * laporan legit, dan nilai yang sudah dikunci tidak boleh digeser.
 */
final class ShiftSidang extends Action
{
    /**
     * @return array{dipindah: array<int, int>, dibatalkan: array<int, int>, tergeser: array<int, int>}
     */
    public function handle(mixed ...$arguments): array
    {
        [$jadwal, $newTanggal, $kaprog] = $arguments;

        if (! $jadwal instanceof JadwalSidang || ! $kaprog instanceof User) {
            throw new RuntimeException('Jadwal dan Kaprog wajib diisi.');
        }

        if ($newTanggal !== null && ! is_string($newTanggal)) {
            throw new RuntimeException('Tanggal baru harus berupa tanggal atau null.');
        }

        Gate::forUser($kaprog)->authorize('shift', $jadwal);

        return $this->transaction(function () use ($jadwal, $newTanggal): array {
            $rows = JadwalSidang::query()
                ->with(['pengajuanSiswa', 'externalExaminer'])
                ->lockForUpdate()
                ->get();

            $moved = $rows->first(fn (JadwalSidang $row): bool => $row->getKey() === $jadwal->getKey());

            if ($moved === null) {
                throw new RuntimeException('Jadwal tidak ditemukan.');
            }

            $proposal = $this->proposal($rows);
            $sourceDate = $proposal[$moved->getKey()]['tanggal'];
            $movedOrder = $proposal[$moved->getKey()]['urutan'];
            $movedRow = $proposal[$moved->getKey()];
            $dibatalkan = [];
            $tergeser = [];

            if ($newTanggal === null) {
                unset($proposal[$moved->getKey()]);
                $dibatalkan[] = $moved->getKey();

                foreach ($proposal as $id => $row) {
                    if ($row['tanggal'] === $sourceDate && $row['urutan'] > $movedOrder) {
                        $proposal[$id]['urutan'] = $row['urutan'] - 1;
                    }
                }
            } else {
                $tail = collect($proposal)->where('tanggal', $newTanggal)->max('urutan');
                $proposal[$moved->getKey()]['tanggal'] = $newTanggal;
                $proposal[$moved->getKey()]['urutan'] = (int) $tail + 1;

                foreach ($proposal as $id => $row) {
                    if ($id !== $moved->getKey() && $row['tanggal'] === $sourceDate && $row['urutan'] > $movedOrder) {
                        $proposal[$id]['tanggal'] = $this->nextWorkingDay($row['tanggal']);
                        $tergeser[] = (int) $id;
                    }
                }
            }

            $conflicts = $this->conflicts($proposal, $movedRow);

            if ($conflicts !== []) {
                throw ValidationException::withMessages(['jadwal' => $conflicts]);
            }

            foreach ($proposal as $id => $row) {
                JadwalSidang::query()->whereKey($id)->update([
                    'tanggal' => $row['tanggal'],
                    'urutan' => $row['urutan'],
                ]);
            }

            if ($newTanggal === null) {
                JadwalSidang::query()->whereKey($moved->getKey())->delete();
            }

            $this->audit(
                $moved,
                $newTanggal === null ? 'jadwal_sidang_dibatalkan' : 'jadwal_sidang_dipindah',
                $sourceDate,
                $newTanggal ?? 'dibatalkan',
                ['tergeser' => $tergeser, 'tanggal_asal' => $sourceDate],
                $newTanggal === null ? 'Jadwal sidang dibatalkan' : 'Jadwal sidang dipindah',
            );

            return [
                'dipindah' => $newTanggal === null ? [] : [$moved->getKey()],
                'dibatalkan' => $dibatalkan,
                'tergeser' => $tergeser,
            ];
        });
    }

    /**
     * @param  Collection<int, JadwalSidang>  $rows
     * @return array<int, array{tanggal: string, urutan: int, examiner: int, siswa: string, tanggal_selesai: ?string, legit: bool, locked: bool}>
     */
    private function proposal($rows): array
    {
        $proposal = [];

        foreach ($rows as $row) {
            $siswa = $row->pengajuanSiswa;

            $proposal[$row->getKey()] = [
                'tanggal' => $row->tanggal->toDateString(),
                'urutan' => (int) $row->urutan,
                'examiner' => (int) $row->external_examiner_id,
                'siswa' => (string) ($siswa?->siswa?->name ?? '-'),
                'tanggal_selesai' => $siswa?->tanggal_selesai_pkl?->toDateString(),
                'legit' => (bool) $siswa?->hasLegitReport(),
                'locked' => $row->isScored(),
            ];
        }

        return $proposal;
    }

    /**
     * @param  array<int, array<string, mixed>>  $proposal
     * @param  array<string, mixed>  $movedRow
     * @return array<int, string>
     */
    private function conflicts(array $proposal, array $movedRow): array
    {
        $conflicts = [];

        if ($movedRow['legit'] === false) {
            $conflicts[] = __('pkl.sidang.conflict_not_legit', ['siswa' => $movedRow['siswa']]);
        }

        if ($movedRow['locked'] === true) {
            $conflicts[] = __('pkl.sidang.conflict_scored', ['siswa' => $movedRow['siswa']]);
        }

        $slots = [];
        $examinerSlots = [];

        foreach ($proposal as $row) {
            $slot = $row['tanggal'].'#'.$row['urutan'];
            $slots[$slot][] = $row['siswa'];
            $examinerSlots[$slot.'#'.$row['examiner']][] = $row['siswa'];

            if ($row['tanggal_selesai'] !== null && $row['tanggal'] > $row['tanggal_selesai']) {
                $conflicts[] = __('pkl.sidang.conflict_beyond_period', [
                    'siswa' => $row['siswa'],
                    'tanggal' => $row['tanggal_selesai'],
                ]);
            }
        }

        foreach ($slots as $slot => $names) {
            if (count($names) > 1) {
                $conflicts[] = __('pkl.sidang.conflict_slot', ['slot' => str_replace('#', ' urutan ', $slot)]);
            }
        }

        foreach ($examinerSlots as $slot => $names) {
            if (count($names) > 1) {
                [$tanggal, $urutan, $examiner] = explode('#', $slot);
                $conflicts[] = __('pkl.sidang.conflict_examiner', [
                    'examiner' => $examiner,
                    'slot' => $tanggal.' urutan '.$urutan,
                ]);
            }
        }

        return array_values(array_unique($conflicts));
    }

    /** Geser ke hari kerja (Senin–Jumat) berikutnya; libur nasional tidak dikecualikan. */
    private function nextWorkingDay(string $date): string
    {
        $next = CarbonImmutable::parse($date, JakartaTime::TZ)->addDay();

        while (! $next->isWeekday()) {
            $next = $next->addDay();
        }

        return $next->toDateString();
    }
}
