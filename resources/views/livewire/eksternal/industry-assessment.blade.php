@php
    $submission = $this->submission();
    $definition = $submission?->definition;
@endphp

<div class="space-y-6">
    <header>
        <h1 class="text-[1.5rem] font-semibold leading-tight">Penilaian industri</h1>
        @if ($submission)
            <p class="mt-1 text-[var(--text-muted)]">
                {{ $submission->company?->nama }} · {{ $submission->definition?->nama }}
            </p>
        @endif
    </header>

    @if ($notice)
        <div class="rounded-lg bg-[var(--success-soft)] p-4 text-[0.875rem] text-[var(--success)]" role="status">
            {{ $notice }}
        </div>
    @endif

    @if ($done)
        <p class="text-[0.875rem] text-[var(--text-muted)]">
            Penilaian sudah dikunci dan tidak dapat diubah. Tutup halaman ini.
        </p>
    @elseif ($submission === null)
        <p class="text-[0.875rem] text-[var(--text-muted)]">{{ __('pkl.industri.form_missing') }}</p>
    @else
        <section class="rounded-lg bg-[var(--bg-soft)] p-4">
            <h2 class="text-[1rem] font-semibold">Siswa yang dinilai</h2>
            <ul class="mt-2 space-y-1 text-[0.875rem]">
                @forelse ($this->students() as $row)
                    <li>{{ $row->pengajuanSiswa?->siswa?->name ?? '-' }}</li>
                @empty
                    <li>{{ __('pkl.industri.students_missing') }}</li>
                @endforelse
            </ul>
        </section>

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

                        @if ($item->has_alokasi_waktu)
                            <label for="alokasi-{{ $item->getKey() }}" class="block text-[0.8125rem] font-medium">
                                Alokasi waktu (opsional)
                                <input
                                    id="alokasi-{{ $item->getKey() }}"
                                    type="text"
                                    maxlength="100"
                                    wire:model="alokasi.{{ $item->getKey() }}"
                                    class="pkl-input mt-1"
                                >
                            </label>
                        @endif
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
                    Catatan
                    <textarea id="catatan" wire:model="catatan" rows="3" maxlength="2000" class="pkl-input mt-1"></textarea>
                </label>
            @endif

            <button type="submit" class="pkl-btn pkl-btn-primary min-h-11 w-full" wire:loading.attr="disabled">
                <span wire:loading.remove>Simpan dan selesaikan</span>
                <span wire:loading>Menyimpan...</span>
            </button>

            <p class="text-[0.8125rem] text-[var(--text-muted)]">
                Penilaian ini final dan tidak dapat direvisi setelah disimpan.
            </p>
        </form>
    @endif
</div>