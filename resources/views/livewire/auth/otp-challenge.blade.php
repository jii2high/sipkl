<div class="space-y-6">
    <header>
        <h1 class="text-[1.5rem] font-semibold leading-tight">Verifikasi OTP</h1>
        <p class="mt-1 text-[var(--text-muted)]">
            Kami mengirim 6 digit kode ke email sekolah Anda.
        </p>
    </header>

    @if (session('status'))
        <p class="rounded-md bg-[var(--success-soft)] px-3 py-2 text-[0.8125rem] text-[var(--success)]" role="status">
            {{ session('status') }}
        </p>
    @endif

    <form wire:submit="verify" class="space-y-4">
        <div>
            <x-otp-input wire:model="code" />
            @error('code')
                <p class="mt-2 text-[0.8125rem] text-[var(--danger)]">{{ $message }}</p>
            @enderror
        </div>

        <div x-data="{ seconds: @js($ttlSeconds) }" x-init="setInterval(() => { if (seconds > 0) seconds-- }, 1000)"
            x-on:otp-resent.window="seconds = @js($ttlSeconds)">
            <p class="text-[0.8125rem] text-[var(--text-muted)]">
                Kode berlaku <span class="font-mono" x-text="seconds"></span> detik.
            </p>
        </div>

        <button type="submit" class="pkl-btn pkl-btn-primary w-full" wire:loading.attr="disabled">
            <x-heroicon-o-paper-airplane class="h-5 w-5" aria-hidden="true" />
            <span wire:loading.remove>Verifikasi</span>
            <span wire:loading>Memeriksa…</span>
        </button>
    </form>

    <button type="button" wire:click="resend" class="inline-flex items-center gap-1 text-[0.8125rem] font-medium text-[var(--primary)] underline" wire:loading.attr="disabled">
        <x-heroicon-o-arrow-path class="h-4 w-4" aria-hidden="true" />
        Kirim ulang kode
    </button>
</div>
