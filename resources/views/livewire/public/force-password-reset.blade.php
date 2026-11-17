<main class="auth-page">
    <section class="auth-card" aria-labelledby="reset-title">
        <p class="eyebrow">Akun baru</p>
        <h1 id="reset-title">Atur ulang password</h1>
        <p class="intro">Password baru Anda wajib minimal 8 karakter dan tidak boleh sama dengan NIS/NIP.</p>

        <form wire:submit="submit" novalidate>
            <div class="field">
                <label for="password">Password baru</label>
                <input id="password" type="password" wire:model="password" autocomplete="new-password" required>
                @error('password')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password_confirmation">Konfirmasi password</label>
                <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" required>
                @error('password_confirmation')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit">Simpan password</button>
        </form>
    </section>
</main>

<style>
    :root { color-scheme: light; font-family: system-ui, sans-serif; }
    body { margin: 0; background: #f3f5fb; }
    .auth-page { min-height: 100vh; display: grid; place-items: center; padding: 24px 16px; }
    .auth-card { width: min(100%, 420px); background: #fff; border-radius: 18px; padding: 32px 28px; box-shadow: 0 18px 40px rgba(26, 40, 72, 0.12); }
    .eyebrow { margin: 0 0 8px; text-transform: uppercase; letter-spacing: 0.06em; font-size: 0.74rem; font-weight: 700; color: #2a5ab4; }
    h1 { margin: 0; font-size: 1.8rem; }
    .intro { margin: 8px 0 20px; color: #53627f; }
    .field { margin-bottom: 18px; }
    label { display: block; margin-bottom: 7px; font-weight: 600; }
    input { width: 100%; min-height: 46px; padding: 10px 12px; border: 1px solid #b7bfd0; border-radius: 8px; }
    button { width: 100%; min-height: 46px; border: none; border-radius: 8px; background: #2456a6; color: white; font-weight: 700; cursor: pointer; }
    .error { margin-top: 8px; color: #c33131; }
    @media (max-width: 380px) { .auth-card { padding: 24px 18px; } }
</style>
