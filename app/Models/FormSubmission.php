<?php

namespace App\Models;

use App\Services\MagicLinkService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'form_definition_id',
    'form_type',
    'jadwal_sidang_id',
    'pengajuan_siswa_id',
    'company_id',
    'academic_period_id',
    'jawaban',
    'is_submitted',
    'submitted_at',
    'revision_count',
])]
class FormSubmission extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jawaban' => 'array',
            'nilai_total' => 'decimal:2',
            'is_submitted' => 'boolean',
            'submitted_at' => 'datetime',
            'revision_count' => 'integer',
        ];
    }

    /** @return BelongsTo<FormDefinition, $this> */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class, 'form_definition_id');
    }

    /** @return BelongsTo<JadwalSidang, $this> */
    public function jadwalSidang(): BelongsTo
    {
        return $this->belongsTo(JadwalSidang::class);
    }

    /** @return BelongsTo<PengajuanSiswa, $this> */
    public function pengajuanSiswa(): BelongsTo
    {
        return $this->belongsTo(PengajuanSiswa::class);
    }

    /** @return HasMany<NilaiSidangRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(NilaiSidangRevision::class);
    }

    /** Window revisi terbuka: penguji masih boleh submit ulang (F9 alur 5). */
    public function openRevision(): ?NilaiSidangRevision
    {
        return $this->revisions()
            ->where('status', 'menunggu_revisi')
            ->where('unlock_expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    /** Permintaan revisi yang belum diputuskan Kaprog. */
    public function pendingRevision(): ?NilaiSidangRevision
    {
        return $this->revisions()->where('status', 'menunggu_persetujuan')->latest('id')->first();
    }

    /**
     * Sesi penguji ini berhak mengubah submission ini: tautan sidang harus menunjuk
     * jadwal submission, tautan unlock harus menunjuk revisi submission (KP-F9-3).
     */
    public function isTargetedBy(ExternalSession $session): bool
    {
        $link = $session->magicLink;

        return match ($link?->type) {
            MagicLinkService::TYPE_EXAMINER_SIDANG => $this->sameExaminerDay($link),
            MagicLinkService::TYPE_EXAMINER_UNLOCK => $this->revisions()->whereKey($link->reference_id)->exists(),
            default => false,
        };
    }

    /** Satu tautan penguji berlaku untuk semua siswa penguji itu pada hari sidang. */
    private function sameExaminerDay(MagicLink $link): bool
    {
        $anchor = JadwalSidang::query()->find($link->reference_id);
        $mine = $this->jadwalSidang;

        return $anchor !== null
            && $mine !== null
            && (int) $anchor->external_examiner_id === (int) $mine->external_examiner_id
            && $anchor->tanggal->toDateString() === $mine->tanggal->toDateString();
    }
}
