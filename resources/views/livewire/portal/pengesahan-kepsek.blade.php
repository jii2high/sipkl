@php
    $signatures = $this->signatures();
    $selectedIds = array_map('intval', $selected);
    $allSelected = $signatures !== []
        && count(array_intersect($selectedIds, array_map(fn ($row) => (int) $row->getKey(), $signatures))) === count($signatures);
@endphp

<section class="flex flex-col gap-4">
    <div>
        <h1 class="text-[1.125rem] font-semibold">{{ __('pkl.portal.kepsek_judul') }}</h1>
        <p class="mt-1 text-[0.8125rem] text-[var(--text-muted)]">
            Tanda tangan step 5 menutup rantai 5-TTD pada satu laporan. Tiap laporan diproses independen.
        </p>
    </div>

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

    @if ($bulkResult !== null && $bulkResult['failed'] !== [])
        <div role="status" class="rounded-[var(--radius-md)] border border-[var(--warning)] bg-[var(--warning-soft)] p-3 text-[0.875rem] text-[var(--warning)]">
            <p class="font-medium">Laporan yang gagal:</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($bulkResult['failed'] as $failure)
                    <li>{{ $this->studentName((int) $failure['id']) }} — {{ $failure['alasan'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($signatures === [])
        <div class="pkl-card">
            <p class="text-[0.875rem] font-medium">Tidak ada laporan yang menunggu tanda tangan Anda.</p>
            <p class="mt-1 text-[0.8125rem] text-[var(--text-muted)]">Laporan muncul setelah step 4 (Kepala Industri) selesai ditandatangani.</p>
        </div>
    @else
        <div class="flex items-center justify-between gap-2">
            <p class="text-[0.8125rem] text-[var(--text-muted)]">{{ count($signatures) }} laporan menunggu TTD Anda.</p>
            <button
                type="button"
                class="pkl-btn pkl-btn-secondary"
                wire:click="toggleAll"
            >
                <x-heroicon-o-check class="h-5 w-5" aria-hidden="true" />
                {{ $allSelected ? 'Kosongkan pilihan' : 'Pilih semua' }}
            </button>
        </div>

        @foreach ($signatures as $signature)
            @php
                $student = $signature->pengajuanSiswa?->siswa;
                $company = $signature->pengajuanSiswa?->company;
            @endphp

            <article wire:key="ttd-{{ $signature->getKey() }}" class="pkl-card flex flex-col gap-3">
                <label class="flex min-h-[44px] items-start gap-3">
                    <input
                        type="checkbox"
                        class="mt-[11px] h-5 w-5 shrink-0 rounded border-[var(--border)] text-[var(--primary)] focus:ring-2 focus:ring-[var(--focus-ring)]"
                        value="{{ $signature->getKey() }}"
                        wire:model.live="selected"
                        aria-label="Pilih laporan {{ $student?->name }}"
                    >
                    <span class="flex flex-1 flex-col gap-1">
                        <span class="font-medium">{{ $student?->name ?? 'Siswa' }}</span>
                        <span class="text-[0.8125rem] text-[var(--text-muted)]">{{ $company?->nama ?? 'Perusahaan' }}</span>
                    </span>
                </label>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="pkl-chip pkl-chip-info">{{ $this->stepLabel(5) }}</span>
                    @if ($signature->laporanVersion !== null)
                        <a
                            class="text-[0.8125rem] font-medium text-[var(--primary)] underline"
                            href="{{ route('laporan.download', ['version' => $signature->laporan_version_id]) }}"
                        >Unduh laporan v{{ $signature->laporanVersion->version_number }}</a>
                    @endif
                    <span class="text-[0.75rem] text-[var(--text-muted)]">
                        Menunggu sejak {{ $signature->created_at?->timezone(config('pkl.timezone.operational'))->format('d M Y H:i') }} WIB
                    </span>
                </div>

                @if ($rejectingId === (int) $signature->getKey())
                    <div class="flex flex-col gap-2 rounded-[var(--radius-md)] bg-[var(--surface-muted)] p-3">
                        <label class="flex flex-col gap-1 text-[0.8125rem]">
                            <span class="font-medium">Catatan penolakan *</span>
                            <textarea
                                rows="3"
                                maxlength="2000"
                                wire:model="rejectNote"
                                class="pkl-input"
                                @error('rejectNote') aria-invalid="true" @enderror
                            ></textarea>
                            @error('rejectNote') <span class="text-[var(--danger)]">{{ $message }}</span> @enderror
                            <span class="text-[0.75rem] text-[var(--text-muted)]">Minimal 10 karakter. Siswa mengunggah revisi dari step ini.</span>
                        </label>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="pkl-btn pkl-btn-danger flex-1"
                                wire:click="reject({{ $signature->getKey() }})"
                                wire:loading.attr="disabled"
                                wire:target="reject"
                            >
                                <x-heroicon-o-check class="h-5 w-5" aria-hidden="true" />
                                Simpan penolakan
                            </button>
                            <button
                                type="button"
                                class="pkl-btn pkl-btn-secondary flex-1"
                                wire:click="cancelReject"
                            >
                                <x-heroicon-o-x-mark class="h-5 w-5" aria-hidden="true" />
                                Batal
                            </button>
                        </div>
                    </div>
                @else
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="pkl-btn pkl-btn-primary flex-1"
                            wire:click="approve({{ $signature->getKey() }})"
                            wire:confirm="Tanda tangani step 5 atas nama Anda untuk laporan ini?"
                            wire:loading.attr="disabled"
                            wire:target="approve"
                        >
                            <x-heroicon-o-check class="h-5 w-5" aria-hidden="true" />
                            <span wire:loading.remove wire:target="approve">Setujui (TTD step 5)</span>
                            <span wire:loading wire:target="approve">Menyimpan...</span>
                        </button>
                        <button
                            type="button"
                            class="pkl-btn pkl-btn-secondary flex-1"
                            wire:click="startReject({{ $signature->getKey() }})"
                        >
                            <x-heroicon-o-x-mark class="h-5 w-5" aria-hidden="true" />
                            Tolak
                        </button>
                    </div>
                @endif
            </article>
        @endforeach
    @endif

    @if ($selectedIds !== [])
        <div aria-hidden="true" class="h-20"></div>
    @endif
</section>

@if ($selectedIds !== [])
    <div class="fixed inset-x-0 bottom-11 z-10 border-t border-[var(--border)] bg-[var(--surface)]">
        <div class="mx-auto flex w-full max-w-[420px] items-center justify-between gap-3 px-4 py-2">
            <span class="text-[0.8125rem] text-[var(--text-muted)]">{{ count($selectedIds) }} dipilih</span>
            <button
                type="button"
                class="pkl-btn pkl-btn-primary"
                wire:click="approveSelected"
                wire:confirm="Tanda tangani step 5 untuk {{ count($selectedIds) }} laporan terpilih? Tiap laporan diproses independen."
                wire:loading.attr="disabled"
                wire:target="approveSelected"
            >
                <x-heroicon-o-check class="h-5 w-5" aria-hidden="true" />
                <span wire:loading.remove wire:target="approveSelected">Setujui ({{ count($selectedIds) }})</span>
                <span wire:loading wire:target="approveSelected">Memproses...</span>
            </button>
        </div>
    </div>
@endif
