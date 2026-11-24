<div class="space-y-6">
    @if ($invalid)
        <header>
            <h1 class="text-[1.5rem] font-semibold leading-tight">Tautan tidak berlaku</h1>
            <p class="mt-1 text-[var(--text-muted)]">{{ __('pkl.eksternal.invalid_link') }}</p>
        </header>

        <a href="{{ route('eksternal.link') }}" class="pkl-btn pkl-btn-primary min-h-11 w-full text-center">
            <x-heroicon-o-arrow-left class="h-5 w-5" aria-hidden="true" />
            Minta tautan baru
        </a>
    @else
        <header>
            <h1 class="text-[1.5rem] font-semibold leading-tight">Penilaian industri</h1>
            <p class="mt-1 text-[var(--text-muted)]">
                {{ $companyName ?: 'Perusahaan' }}. Masukkan kode OTP yang dikirim ke email pembimbing industri.
            </p>
        </header>

        <form wire:submit="verify" class="space-y-4">
            <div>
                <x-otp-input wire:model="code" />
                @error('code')
                    <p class="mt-2 text-center text-[0.8125rem] text-[var(--danger)]">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="pkl-btn pkl-btn-primary min-h-11 w-full" wire:loading.attr="disabled">
                <x-heroicon-o-paper-airplane class="h-5 w-5" aria-hidden="true" />
                <span wire:loading.remove>Verifikasi kode</span>
                <span wire:loading>Memeriksa...</span>
            </button>
        </form>

        <p class="text-[0.8125rem] text-[var(--text-muted)]">
            Kode berlaku 5 menit dan maksimal 3 percobaan. Tautan ini hanya dapat dipakai sekali.
            Jika tautan tidak berlaku, minta penerbitan ulang kepada guru pembimbing atau Hubin.
        </p>
    @endif
</div>