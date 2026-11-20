<div class="space-y-6">
    @if ($invalid)
        <header>
            <h1 class="text-[1.5rem] font-semibold leading-tight">Tautan tidak berlaku</h1>
            <p class="mt-1 text-[var(--text-muted)]">{{ __('pkl.eksternal.invalid_link') }}</p>
        </header>
    @else
        <header>
            <h1 class="text-[1.5rem] font-semibold leading-tight">{{ __('pkl.sidang.entry_title') }}</h1>
            <p class="mt-1 text-[var(--text-muted)]">
                @if ($examinerName)
                    Penguji {{ $examinerName }}@if ($tanggal), sidang {{ $tanggal }}@endif.
                @endif
                Masukkan PIN yang dikirim bersama tautan ini.
            </p>
        </header>

        <form wire:submit="verify" class="space-y-4">
            <div>
                <label for="pin" class="block text-[0.8125rem] font-medium">{{ __('pkl.sidang.pin_label') }}</label>
                <input
                    id="pin"
                    type="text"
                    inputmode="numeric"
                    maxlength="4"
                    wire:model="pin"
                    class="pkl-input mt-1 text-center text-[1.25rem] tracking-[0.5em]"
                    autocomplete="one-time-code"
                    @error('pin') aria-invalid="true" @enderror
                >
                @error('pin')
                    <p class="mt-2 text-center text-[0.8125rem] text-[var(--danger)]">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="pkl-btn pkl-btn-primary min-h-11 w-full" wire:loading.attr="disabled">
                <span wire:loading.remove>Masuk</span>
                <span wire:loading>Memeriksa...</span>
            </button>
        </form>

        <p class="text-[0.8125rem] text-[var(--text-muted)]">
            PIN maksimal 3 percobaan. Jika tautan tidak berlaku, minta penerbitan ulang kepada Kaprog.
        </p>
    @endif
</div>