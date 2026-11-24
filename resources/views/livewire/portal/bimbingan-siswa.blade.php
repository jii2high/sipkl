@php
    $pengajuan = $this->pengajuan();
    $progress = $this->progress();
    $rejected = $this->rejectedSignature();
    $percent = $progress['min'] > 0
        ? min(100, (int) round($progress['count'] / $progress['min'] * 100))
        : 0;
@endphp

<section class="flex flex-col gap-4">
    <h1 class="text-[1.125rem] font-semibold">Bimbingan laporan</h1>

    @if ($flash !== null)
        <p
            role="status"
            @class([
                'rounded-[var(--radius-md)] border p-3 text-[0.875rem]',
                'border-[var(--success)] bg-[var(--success-soft)] text-[var(--success)]' => $flashTone === 'success',
                'border-[var(--danger)] bg-[var(--danger-soft)] text-[var(--danger)]' => $flashTone === 'danger',
                'border-[var(--border)] bg-[var(--surface-muted)] text-[var(--text)]' => ! in_array($flashTone, ['success', 'danger'], true),
            ])
        >{{ $flash }}</p>
    @endif

    @if ($pengajuan === null)
        <div class="pkl-card flex flex-col gap-3">
            <p class="text-[0.875rem] text-[var(--text-muted)]">Bimbingan tersedia setelah Anda berstatus Sedang PKL.</p>
            <a href="{{ route('portal.siswa') }}" class="pkl-btn pkl-btn-secondary w-full">
                <x-heroicon-o-arrow-left class="h-5 w-5" aria-hidden="true" />
                Kembali ke pengajuan
            </a>
        </div>
    @else
        @if ($pengajuan->is_provisional)
            <div class="rounded-[var(--radius-md)] border border-[var(--warning)] bg-[var(--warning-soft)] p-3 text-[0.875rem] text-[var(--warning)]">
                {{ __('pkl.bimbingan.provisional_locked') }}
            </div>
        @endif

        <div class="pkl-card flex flex-col gap-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-[1.125rem] font-semibold">Progres bimbingan</h2>
                <span class="text-[0.8125rem] text-[var(--text-muted)]">{{ __('pkl.bimbingan.progress', $progress) }}</span>
            </div>

            <div
                class="h-2 w-full rounded-full bg-[var(--surface-muted)]"
                role="progressbar"
                aria-valuemin="0"
                aria-valuemax="{{ $progress['min'] }}"
                aria-valuenow="{{ $progress['count'] }}"
                aria-label="Progres bimbingan"
            >
                <div class="h-2 rounded-full bg-[var(--accent-strong)]" style="width: {{ $percent }}%"></div>
            </div>

            @if ($progress['count'] >= $progress['min'])
                <p class="text-[0.875rem] text-[var(--success)]">Anda bisa menandai bagian akhir laporan.</p>
            @else
                <p class="text-[0.875rem] text-[var(--text-muted)]">
                    Unggah bimbingan sampai minimal {{ $progress['min'] }} interaksi sebelum menandai bagian akhir.
                </p>
            @endif
        </div>

        @if ($rejected !== null)
            <div class="pkl-card flex flex-col gap-3 border-[var(--danger)]">
                <div>
                    <h2 class="text-[1.125rem] font-semibold">Revisi laporan</h2>
                    <p class="mt-1 text-[0.875rem]">
                        Step {{ $rejected->step_order }} ({{ $rejected->signer_type }}) ditolak:
                        “{{ $rejected->rejection_note }}”
                    </p>
                    <p class="mt-1 text-[0.8125rem] text-[var(--text-muted)]">
                        Rantai pengesahan dilanjutkan langsung dari step {{ $rejected->step_order }}.
                    </p>
                </div>

                <label class="flex flex-col gap-1 text-[0.8125rem]">
                    <span class="font-medium">Berkas revisi *</span>
                    <input
                        type="file"
                        wire:model="revisionFile"
                        accept=".pdf,.doc,.docx"
                        class="pkl-input"
                        @error('revisionFile') aria-invalid="true" @enderror
                    >
                    @error('revisionFile') <span class="text-[var(--danger)]">{{ $message }}</span> @enderror
                </label>

                <button
                    type="button"
                    class="pkl-btn pkl-btn-primary w-full"
                    wire:click="uploadRevision"
                    wire:loading.attr="disabled"
                    wire:target="uploadRevision, revisionFile"
                >
                    <x-heroicon-o-arrow-up-tray class="h-5 w-5" aria-hidden="true" />
                    <span wire:loading.remove wire:target="uploadRevision, revisionFile">Unggah revisi laporan</span>
                    <span wire:loading wire:target="uploadRevision, revisionFile">Mengunggah...</span>
                </button>
            </div>
        @endif

        <div class="pkl-card flex flex-col gap-3">
            <div>
                <h2 class="text-[1.125rem] font-semibold">Unggah bimbingan</h2>
                <p class="mt-1 text-[0.8125rem] text-[var(--text-muted)]">
                    Berkas PDF/DOC/DOCX maksimal {{ config('pkl.bimbingan.upload.max_mb') }} MB. Berkas disimpan privat.
                </p>
            </div>

            <label class="flex flex-col gap-1 text-[0.8125rem]">
                <span class="font-medium">Berkas bimbingan *</span>
                <input
                    type="file"
                    wire:model="file"
                    accept=".pdf,.doc,.docx"
                    class="pkl-input"
                    @disabled($pengajuan->is_provisional)
                    @error('file') aria-invalid="true" @enderror
                >
                @error('file') <span class="text-[var(--danger)]">{{ $message }}</span> @enderror
            </label>

            <label class="flex flex-col gap-1 text-[0.8125rem]">
                <span class="font-medium">Keterangan</span>
                <textarea
                    rows="2"
                    maxlength="2000"
                    wire:model="keterangan"
                    class="pkl-input"
                    @disabled($pengajuan->is_provisional)
                ></textarea>
                @error('keterangan') <span class="text-[var(--danger)]">{{ $message }}</span> @enderror
            </label>

            <button
                type="button"
                class="pkl-btn pkl-btn-primary w-full"
                wire:click="submit"
                wire:loading.attr="disabled"
                wire:target="submit, file"
                @disabled($pengajuan->is_provisional)
            >
                <x-heroicon-o-arrow-up-tray class="h-5 w-5" aria-hidden="true" />
                <span wire:loading.remove wire:target="submit, file">Unggah bimbingan</span>
                <span wire:loading wire:target="submit, file">Mengunggah...</span>
            </button>
        </div>

        <section class="flex flex-col gap-3">
            <h2 class="text-[1.125rem] font-semibold">Riwayat bimbingan</h2>

            @forelse ($this->submissions() as $submission)
                <article wire:key="bimbingan-{{ $submission->getKey() }}" class="pkl-card flex flex-col gap-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-status-badge :status="$submission->status" />
                            @if ($submission->is_bagian_akhir)
                                <x-status-badge status="bagian_akhir" :label="'Bagian akhir'" />
                            @endif
                        </div>
                        <span class="text-[0.75rem] text-[var(--text-muted)]">
                            {{ $submission->created_at?->timezone(config('pkl.timezone.operational'))->format('d M Y H:i') }} WIB
                        </span>
                    </div>

                    @if ($submission->keterangan)
                        <p class="text-[0.875rem]">{{ $submission->keterangan }}</p>
                    @endif

                    @if ($submission->feedback_note)
                        <p class="rounded-[var(--radius-md)] bg-[var(--surface-muted)] p-3 text-[0.875rem]">
                            Feedback guru: {{ $submission->feedback_note }}
                        </p>
                    @endif

                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <a
                            class="pkl-btn pkl-btn-secondary"
                            href="{{ route('bimbingan.download', ['id' => $submission->getKey()]) }}"
                        >
                            <x-heroicon-o-arrow-down-tray class="h-5 w-5" aria-hidden="true" />
                            Unduh berkas
                        </a>

                        @if (! $submission->is_bagian_akhir && $progress['count'] >= $progress['min'])
                            <button
                                type="button"
                                class="pkl-btn pkl-btn-secondary"
                                wire:click="markFinal({{ $submission->getKey() }})"
                                wire:loading.attr="disabled"
                            >
                                <x-heroicon-o-check-badge class="h-5 w-5" aria-hidden="true" />
                                Tandai bagian akhir
                            </button>
                        @endif
                    </div>
                </article>
            @empty
                <div class="pkl-card flex flex-col items-center gap-2 text-center text-[0.875rem] text-[var(--text-muted)]">
                    <x-heroicon-o-book-open class="h-8 w-8" aria-hidden="true" />
                    <span>Belum ada bimbingan yang diunggah.</span>
                </div>
            @endforelse
        </section>
    @endif
</section>
