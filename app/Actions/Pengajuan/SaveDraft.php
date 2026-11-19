<?php

namespace App\Actions\Pengajuan;

use App\Enums\PengajuanStatus;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\PengajuanSiswa;
use App\Models\User;
use App\Services\CompanyResolver;
use App\Support\ContactNormalizer;
use Illuminate\Validation\ValidationException;

/**
 * Simpan draf pengajuan siswa (F3 alur 1-2). Perusahaan boleh dipilih dari master
 * atau diketik baru (`source = siswa`, Q-H). Draft belum memakai kuota dan belum
 * menyimpan snapshot; snapshot dibekukan saat submit.
 */
final class SaveDraft extends PengajuanAction
{
    public function __construct(private readonly CompanyResolver $resolver) {}

    public function handle(mixed ...$arguments): PengajuanSiswa
    {
        /** @var User $siswa */
        /** @var AcademicPeriod $period */
        /** @var array<string, mixed> $data */
        /** @var PengajuanSiswa|null $existing */
        [$siswa, $period, $data, $existing] = $arguments + [null, null, [], null];

        if ($existing instanceof PengajuanSiswa) {
            if ($existing->status === PengajuanStatus::Draft->value) {
                $this->authorize('update', $existing, $siswa);
            } else {
                $this->authorize('reopen', $existing, $siswa);
            }

            $period = $existing->academicPeriod ?? $period;
        } else {
            $this->authorize('create', PengajuanSiswa::class, $siswa);
        }

        return $this->transaction(function () use ($siswa, $period, $data, $existing): PengajuanSiswa {
            $company = $this->resolveCompany($siswa, $data);

            $this->assertNoOtherActive($siswa, $period, $existing);

            $pengajuan = $existing ?? new PengajuanSiswa([
                'academic_period_id' => $period->getKey(),
                'siswa_id' => $siswa->getKey(),
                'status' => PengajuanStatus::Draft->value,
            ]);

            $pengajuan->company_id = $company->getKey();
            $pengajuan->save();

            return $pengajuan->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveCompany(User $siswa, array $data): Company
    {
        if (filled($data['company_id'] ?? null)) {
            $company = Company::query()
                ->whereNull('merged_into_id')
                ->find($data['company_id']);

            if ($company === null) {
                throw ValidationException::withMessages([
                    'company_id' => __('pkl.pengajuan.company_missing'),
                ]);
            }

            return $company;
        }

        $input = (array) ($data['company'] ?? []);

        foreach (['nama', 'alamat'] as $field) {
            if (blank($input[$field] ?? null)) {
                throw ValidationException::withMessages([
                    'company.'.$field => __('validation.required', [
                        'attribute' => $field === 'nama' ? 'Nama perusahaan' : 'Alamat perusahaan',
                    ]),
                ]);
            }
        }

        $check = $this->resolver->inspect($input);

        if ($check->hasStrictConflicts()) {
            $messages = [];

            foreach ($check->strict as $field => $existing) {
                $messages['company.'.$field] = __('pkl.company.duplicate', [
                    'field' => __('pkl.company.field.'.$field),
                    'nama' => $existing->nama,
                ]);
            }

            throw ValidationException::withMessages($messages);
        }

        $keys = ContactNormalizer::companyKeys($input);

        return Company::query()->create([
            'nama' => trim((string) $input['nama']),
            'alamat' => trim((string) $input['alamat']),
            'email' => $keys['email'] ?? null,
            'website' => $keys['website'] ?? null,
            'telp_fax' => $keys['telp_fax'] ?? null,
            'cp_name' => filled($input['cp_name'] ?? null) ? trim((string) $input['cp_name']) : null,
            'cp_phone' => filled($input['cp_phone'] ?? null) ? trim((string) $input['cp_phone']) : null,
            'source' => 'siswa',
            'created_by' => $siswa->getKey(),
        ]);
    }

    private function assertNoOtherActive(User $siswa, AcademicPeriod $period, ?PengajuanSiswa $existing): void
    {
        $conflict = PengajuanSiswa::query()
            ->where('siswa_id', $siswa->getKey())
            ->where('academic_period_id', $period->getKey())
            ->whereNotIn('status', [
                PengajuanStatus::Rejected->value,
                PengajuanStatus::Withdrawn->value,
            ])
            ->when($existing instanceof PengajuanSiswa, fn ($query) => $query->whereKeyNot($existing->getKey()))
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'pengajuan' => __('pkl.pengajuan.active_exists'),
            ]);
        }
    }
}
