<div class="space-y-6">
    <header>
        <h1 class="text-[1.5rem] font-semibold leading-tight">Ganti Kata Sandi</h1>
        <p class="mt-1 text-[var(--text-muted)]">
            Ini login pertama Anda. Buat kata sandi baru minimal 8 karakter dan tidak sama dengan NIS/NIP.
        </p>
    </header>

    <form wire:submit="submit" class="space-y-4">
        <div>
            <label for="password" class="mb-1 block text-[0.8125rem] font-medium">Kata sandi baru</label>
            <input
                id="password"
                type="password"
                wire:model="password"
                autocomplete="new-password"
                autofocus
                class="pkl-input"
                @error('password') aria-invalid="true" @enderror
            >
            @error('password')
                <p class="mt-1 text-[0.8125rem] text-[var(--danger)]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-[0.8125rem] font-medium">Ulangi kata sandi baru</label>
            <input
                id="password_confirmation"
                type="password"
                wire:model="password_confirmation"
                autocomplete="new-password"
                class="pkl-input"
            >
        </div>

        <button type="submit" class="pkl-btn pkl-btn-primary w-full" wire:loading.attr="disabled">
            <x-heroicon-o-key class="h-5 w-5" aria-hidden="true" />
            <span wire:loading.remove>Simpan dan lanjut</span>
            <span wire:loading>Menyimpan…</span>
        </button>
    </form>
</div>
