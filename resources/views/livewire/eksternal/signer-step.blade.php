@php
    $signature = $this->signature();
@endphp

<div class="space-y-6">
    <header>
        <h1 class="text-[1.5rem] font-semibold leading-tight">Pengesahan laporan PKL</h1>
        <p class="mt-1 text-[var(--text-muted)]">
            {{ $signature->pengajuanSiswa?->siswa?->name }} — {{ $signature->pengajuanSiswa?->company?->nama }}
        </p>
    </header>

    <x-signature-stepper :steps="$this->steps()" />

    @if ($done)
        <div class="rounded-lg bg-[var(--success-soft)] p-4 text-[0.875rem] text-[var(--success)]" role="status">
            {{ $message }}
        </div>
    @elseif ($signature->status === 'pending')
        <section class="space-y-4">
            <a
                href="{{ route('eksternal.laporan', ['signature' => $signature->getKey()]) }}"
                class="pkl-btn pkl-btn-secondary min-h-11 w-full text-center"
            >
                Unduh berkas laporan
            </a>

            <button type="button" wire:click="approve" class="pkl-btn pkl-btn-primary min-h-11 w-full" wire:loading.attr="disabled">
                <span wire:loading.remove>Setujui dan tanda tangani</span>
                <span wire:loading>Memproses...</span>
            </button>

            <div class="space-y-2 border-t border-[var(--border)] pt-4">
                <label for="note" class="block text-[0.8125rem] font-medium">
                    Catatan penolakan (wajib bila menolak, minimal 10 karakter)
                </label>
                <textarea
                    id="note"
                    wire:model="note"
                    rows="3"
                    maxlength="2000"
                    class="pkl-input"
                    @error('note') aria-invalid="true" @enderror
                ></textarea>
                @error('note')
                    <p class="text-[0.8125rem] text-[var(--danger)]">{{ $message }}</p>
                @enderror
                <button type="button" wire:click="reject" class="pkl-btn pkl-btn-secondary min-h-11 w-full text-[var(--danger)]" wire:loading.attr="disabled">
                    Tolak dan minta revisi
                </button>
            </div>
        </section>
    @else
        <p class="text-[0.875rem] text-[var(--text-muted)]">
            Langkah ini sudah {{ $signature->status === 'signed' ? 'ditandatangani' : 'ditolak' }}.
            Tidak ada tindakan lain yang diperlukan.
        </p>
    @endif
</div>