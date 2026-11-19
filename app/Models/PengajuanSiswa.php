<?php

namespace App\Models;

use App\Enums\PengajuanStatus;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'pengajuan_siswa')]
#[Fillable([
    'pengajuan_pkl_id',
    'company_id',
    'academic_period_id',
    'siswa_id',
    'status',
    'snapshot_company_json',
    'surat_balasan_path',
    'surat_balasan_hasil',
    'surat_balasan_uploaded_at',
    'accepted_by',
    'accepted_at',
    'is_provisional',
    'dokumen_fisik_diterima_at',
    'dokumen_fisik_confirmed_by',
    'last_provisional_reminder_at',
    'tanggal_mulai_pkl',
    'tanggal_selesai_pkl',
    'withdraw_requested_at',
    'withdraw_requested_by',
    'withdraw_reason',
    'withdraw_approved_by',
    'withdrawn_at',
    'resubmitted_from_id',
])]
class PengajuanSiswa extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_company_json' => 'array',
            'surat_balasan_uploaded_at' => 'datetime',
            'accepted_at' => 'datetime',
            'is_provisional' => 'boolean',
            'dokumen_fisik_diterima_at' => 'datetime',
            'last_provisional_reminder_at' => 'date',
            'tanggal_mulai_pkl' => 'date',
            'tanggal_selesai_pkl' => 'date',
            'withdraw_requested_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<AcademicPeriod, $this> */
    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    /** @return BelongsTo<PengajuanPkl, $this> */
    public function pengajuanPkl(): BelongsTo
    {
        return $this->belongsTo(PengajuanPkl::class, 'pengajuan_pkl_id');
    }

    /** Pengajuan masih dihitung aktif (BR-F3-1). */
    public function isActive(): bool
    {
        return ! in_array($this->status, [
            PengajuanStatus::Rejected->value,
            PengajuanStatus::Withdrawn->value,
        ], true);
    }

    /** Kontak perusahaan boleh tampil utuh setelah Kaprog menyetujui (PRD F3 alur 1). */
    public function contactUnlocked(): bool
    {
        return ! in_array($this->status, [
            PengajuanStatus::Draft->value,
            PengajuanStatus::MenungguVerifikasiKaprog->value,
            PengajuanStatus::DitolakKaprog->value,
        ], true);
    }

    /**
     * Batasi query sesuai scope peran aktif (TRD.md §7.3). Dipakai daftar/antrean
     * Filament agar data di luar scope tidak pernah terkirim ke browser.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleTo(Builder $query, User $user, ?string $role = null): Builder
    {
        $role ??= session('active_role');

        return match ($role) {
            Role::Siswa->value => $query->where('siswa_id', $user->getKey()),
            Role::Kaprog->value => $query->whereHas(
                'siswa',
                fn (Builder $student) => $student->where('jurusan_id', $user->roleScopeId(Role::Kaprog)),
            ),
            Role::Walas->value => $query->whereHas(
                'siswa',
                fn (Builder $student) => $student->where('kelas_id', $user->roleScopeId(Role::Walas)),
            ),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
