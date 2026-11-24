<div class="space-y-6">
    <header>
        <h1 class="text-[1.5rem] font-semibold leading-tight">Minta tautan baru</h1>
        <p class="mt-1 text-[var(--text-muted)]">
            Tautan akses eksternal berlaku 7 hari dan hanya dapat dipakai sekali.
            Jika tautan Anda tidak berlaku lagi, minta penerbitan ulang.
        </p>
    </header>

    @if ($submitted)
        <div class="rounded-lg bg-[var(--surface-muted)] p-4 text-[0.875rem]" role="status">
            Permintaan Anda sudah diteruskan ke Hubin. Jika email terdaftar, tautan baru akan dikirim
            melalui email. Untuk mendesak, hubungi Kaprog atau Guru Pembimbing.
        </div>
    @else
        <form wire:submit="submit" class="space-y-4">
            <div>
                <label for="email" class="mb-1 block text-[0.8125rem] font-medium">Email Anda</label>
                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    autocomplete="email"
                    class="pkl-input"
                    @error('email') aria-invalid="true" @enderror
                >
                @error('email')
                    <p class="mt-1 text-[0.8125rem] text-[var(--danger)]">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="pkl-btn pkl-btn-primary min-h-11 w-full" wire:loading.attr="disabled">
                <x-heroicon-o-paper-airplane class="h-5 w-5" aria-hidden="true" />
                <span wire:loading.remove>Kirim permintaan</span>
                <span wire:loading>Memproses...</span>
            </button>
        </form>
    @endif

    <p class="text-[0.8125rem] text-[var(--text-muted)]">
        Permintaan diteruskan ke Hubin. Kaprog atau Guru Pembimbing juga dapat membantu
        menerbitkan tautan pengganti.
    </p>
</div>