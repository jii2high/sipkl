@php($queue = $this->queue())

<section class="flex flex-col gap-4">
    <div>
        <h1 class="text-[1.125rem] font-semibold">{{ __('pkl.portal.walas_judul') }}</h1>
        <p class="mt-1 text-[0.8125rem] text-[var(--text-muted)]">
            Pengajuan dari siswa kelas Anda menunggu tanda tangan. Tanda tangan meneruskan ke surat pengantar Hubin.
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

    @if ($queue === [])
        <div class="pkl-card">
            <p class="text-[0.875rem] font-medium">Tidak ada pengajuan yang menunggu tanda tangan Anda.</p>
            <p class="mt-1 text-[0.8125rem] text-[var(--text-muted)]">Antrean muncul setelah Kaprog menyetujui pengajuan siswa kelas Anda.</p>
        </div>
    @else
        <p class="text-[0.8125rem] text-[var(--text-muted)]">{{ count($queue) }} pengajuan menunggu TTD Walas.</p>

        @foreach ($queue as $pengajuan)
            <article wire:key="gate2-{{ $pengajuan->getKey() }}" class="pkl-card flex flex-col gap-3">
                <div class="flex flex-col gap-1">
                    <p class="font-medium">{{ $pengajuan->siswa?->name ?? 'Siswa' }}</p>
                    <p class="text-[0.8125rem] text-[var(--text-muted)]">{{ $pengajuan->company?->nama ?? 'Perusahaan' }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <x-status-badge :status="$pengajuan->status" />
                    <span class="text-[0.75rem] text-[var(--text-muted)]">
                        Diperbarui {{ $pengajuan->updated_at?->timezone(config('pkl.timezone.operational'))->format('d M Y H:i') }} WIB
                    </span>
                </div>

                @if ($rejectingId === (int) $pengajuan->getKey())
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
                            <span class="text-[0.75rem] text-[var(--text-muted)]">Minimal 10 karakter. Siswa memperbaiki lalu mengirim ulang.</span>
                        </label>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="pkl-btn pkl-btn-danger flex-1"
                                wire:click="reject({{ $pengajuan->getKey() }})"
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
                            wire:click="approve({{ $pengajuan->getKey() }})"
                            wire:confirm="Tandatangani pengajuan {{ $pengajuan->siswa?->name }} sebagai Walas?"
                            wire:loading.attr="disabled"
                            wire:target="approve"
                        >
                            <x-heroicon-o-check class="h-5 w-5" aria-hidden="true" />
                            <span wire:loading.remove wire:target="approve">Tanda tangani</span>
                            <span wire:loading wire:target="approve">Menyimpan...</span>
                        </button>
                        <button
                            type="button"
                            class="pkl-btn pkl-btn-secondary flex-1"
                            wire:click="startReject({{ $pengajuan->getKey() }})"
                        >
                            <x-heroicon-o-x-mark class="h-5 w-5" aria-hidden="true" />
                            Tolak
                        </button>
                    </div>
                @endif
            </article>
        @endforeach
    @endif
</section>