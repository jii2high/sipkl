<div class="space-y-6">
    <header>
        <h1 class="text-[1.5rem] font-semibold leading-tight">Masuk</h1>
        <p class="mt-1 text-[var(--text-muted)]">
            Gunakan email sekolah dan kata sandi Anda.
        </p>
    </header>

    <form wire:submit="submit" class="space-y-4">
        <div>
            <label for="email" class="mb-1 block text-[0.8125rem] font-medium">Email sekolah</label>
            <input
                id="email"
                type="email"
                wire:model="email"
                autocomplete="username"
                autofocus
                class="pkl-input"
                @error('email') aria-invalid="true" @enderror
            >
            @error('email')
                <p class="mt-1 text-[0.8125rem] text-[var(--danger)]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-[0.8125rem] font-medium">Kata sandi</label>
            <input
                id="password"
                type="password"
                wire:model="password"
                autocomplete="current-password"
                class="pkl-input"
                @error('password') aria-invalid="true" @enderror
            >
            @error('password')
                <p class="mt-1 text-[0.8125rem] text-[var(--danger)]">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="pkl-btn pkl-btn-primary w-full" wire:loading.attr="disabled">
            <x-heroicon-o-arrow-right-end-on-rectangle class="h-5 w-5" aria-hidden="true" />
            <span wire:loading.remove>Masuk</span>
            <span wire:loading>Memproses…</span>
        </button>
    </form>

    <p class="text-[0.8125rem] text-[var(--text-muted)]">
        Kata sandi awal sama dengan NIS/NIP. Login pertama meminta kode OTP ke email sekolah.
    </p>
</div>
