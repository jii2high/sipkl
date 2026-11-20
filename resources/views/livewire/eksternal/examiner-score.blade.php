@php
    $submission = $this->submission();
    $definition = $submission?->definition;
    $locked = $submission?->is_submitted === true && $submission?->openRevision() === null;
@endphp

<div class="space-y-6">
    <header>
        <h1 class="text-[1.5rem] font-semibold leading-tight">Penilaian sidang</h1>
        @if ($submission)
            <p class="mt-1 text-[var(--text-muted)]">
                {{ $this->studentName() }} · {{ $submission->jadwalSidang?->tanggal?->format('d/m/Y') }}
            </p>
        @endif
    </header>

    @if ($linkType === 'examiner_sidang' && $this->jadwals()->isNotEmpty())
        <nav aria-label="Daftar siswa" class="flex flex-wrap gap-2">
            @foreach ($this->jadwals() as $jadwal)
                @php
                    $row = $jadwal->submission;
                @endphp
                <button
                    type="button"
                    wire:click="select({{ $jadwal->getKey() }})"
                    class="pkl-btn min-h-11 {{ $jadwalId === $jadwal->getKey() ? 'pkl-btn-primary' : 'pkl-btn-secondary' }}"
                >
                    {{ $jadwal->urutan }}. {{ $jadwal->pengajuanSiswa?->siswa?->name }}
                    @if ($row?->is_submitted)
                        <span aria-label="sudah dinilai">✓</span>
                    @endif
                </button>
            @endforeach
        </nav>
    @endif

    @if ($notice)
        <div class="rounded-lg bg-[var(--success-soft)] p-4 text-[0.875rem] text-[var(--success)]" role="status">
            {{ $notice }}
        </div>
    @endif

    @if ($done)
        <p class="text-[0.875rem] text-[var(--text-muted)]">
            Semua siswa untuk tautan ini sudah dinilai. Tutup halaman ini.
        </p>
    @elseif ($submission === null)
        <p class="text-[0.875rem] text-[var(--text-muted)]">{{ __('pkl.sidang.no_answer') }}</p>
    @elseif ($locked)
        <section class="space-y-4">
            <p class="rounded-lg bg-[var(--bg-soft)] p-4 text-[0.875rem]">
                {{ __('pkl.sidang.score_locked') }}
            </p>

            @if ($submission->pendingRevision() !== null)
                <p class="text-[0.875rem] text-[var(--text-muted)]">{{ __('pkl.sidang.revision_pending') }}</p>
            @else
                <label for="reason" class="block text-[0.8125rem] font-medium">
                    Alasan revisi (minimal 10 karakter)
                </label>
                <textarea
                    id="reason"
                    wire:model="reason"
                    rows="3"
                    maxlength="2000"
                    class="pkl-input"
                    @error('reason') aria-invalid="true" @enderror
                ></textarea>
                @error('reason')
                    <p class="text-[0.8125rem] text-[var(--danger)]">{{ $message }}</p>
                @enderror
                <button type="button" wire:click="requestRevision" class="pkl-btn pkl-btn-secondary min-h-11 w-full">
                    Ajukan revisi nilai
                </button>
            @endif
        </section>
    @else
        @error('items')
            <p class="rounded-lg bg-[var(--danger-soft)] p-4 text-[0.875rem] text-[var(--danger)]">{{ $message }}</p>
        @enderror

        <form wire:submit="submit" class="space-y-5">
            @foreach ($this->sections() as $section)
                <section class="space-y-3">
                    <h2 class="text-[1rem] font-semibold">{{ $section->label }}</h2>

                    @foreach ($section->items as $item)
                        <div class="flex items-center justify-between gap-4">
                            <label for="item-{{ $item->getKey() }}" class="text-[0.875rem]">
                                {{ $item->label }}
                            </label>
                            <input
                                id="item-{{ $item->getKey() }}"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                wire:model="items.{{ $item->getKey() }}"
                                class="pkl-input w-24 text-right"
                                inputmode="decimal"
                            >
                        </div>
                    @endforeach
                </section>
            @endforeach

            @if ($definition?->has_kehadiran)
                <section class="space-y-3 border-t border-[var(--border)] pt-4">
                    <h2 class="text-[1rem] font-semibold">Kehadiran</h2>
                    @foreach (['sakit' => 'Sakit', 'izin' => 'Izin', 'tanpa_keterangan' => 'Tanpa keterangan'] as $field => $label)
                        <div class="flex items-center justify-between gap-4">
                            <label for="hadir-{{ $field }}" class="text-[0.875rem]">{{ $label }}</label>
                            <input
                                id="hadir-{{ $field }}"
                                type="number"
                                min="0"
                                wire:model="kehadiran.{{ $field }}"
                                class="pkl-input w-24 text-right"
                                inputmode="numeric"
                            >
                        </div>
                    @endforeach
                </section>
            @endif

            @if ($definition?->has_catatan)
                <label for="catatan" class="block text-[0.8125rem] font-medium">
                    Catatan untuk siswa
                    <textarea id="catatan" wire:model="catatan" rows="3" maxlength="2000" class="pkl-input mt-1"></textarea>
                </label>
            @endif

            <button type="submit" class="pkl-btn pkl-btn-primary min-h-11 w-full" wire:loading.attr="disabled">
                <span wire:loading.remove>Simpan dan kunci nilai</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </form>
    @endif
</div>