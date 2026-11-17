<main class="reset-page">
    <section class="reset-card" aria-labelledby="reset-title">
        <header class="reset-header">
            <div class="reset-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <rect x="4.5" y="10" width="15" height="10" rx="2.5"></rect>
                    <path d="M8 10V7.5a4 4 0 0 1 8 0V10"></path>
                    <path d="M12 14v2"></path>
                </svg>
            </div>
            <p class="reset-eyebrow">Akun baru</p>
            <h1 id="reset-title">Atur ulang password</h1>
            <p class="reset-intro">Buat password baru untuk menyelesaikan pendaftaran dan mengamankan akun Anda.</p>
        </header>

        <div class="password-guidance">
            <span class="guidance-dot" aria-hidden="true"></span>
            <p><strong>Persyaratan password</strong><br>Minimal 8 karakter dan tidak boleh sama dengan NIS/NIP.</p>
        </div>

        <form wire:submit="submit" novalidate>
            <div class="reset-field">
                <label for="password">Password baru</label>
                <input
                    id="password"
                    class="reset-input"
                    type="password"
                    wire:model="password"
                    autocomplete="new-password"
                    placeholder="Masukkan password baru"
                    required
                >
                @error('password')
                    <p class="reset-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="reset-field">
                <label for="password_confirmation">Konfirmasi password</label>
                <input
                    id="password_confirmation"
                    class="reset-input"
                    type="password"
                    wire:model="password_confirmation"
                    autocomplete="new-password"
                    placeholder="Ulangi password baru"
                    required
                >
                @error('password_confirmation')
                    <p class="reset-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <button class="reset-submit" type="submit" wire:loading.attr="disabled" wire:target="submit">
                <span wire:loading.remove wire:target="submit">Simpan password</span>
                <span wire:loading wire:target="submit">Menyimpan...</span>
            </button>
        </form>
    </section>
</main>

<style>
    :root {
        color-scheme: light;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        color: #172033;
        background: #f3f6fb;
    }

    * { box-sizing: border-box; }
    body { margin: 0; }

    .reset-page {
        min-height: 100vh;
        display: grid;
        place-items: center;
        padding: 32px 16px;
        background:
            radial-gradient(ellipse at 12% 10%, rgb(36 86 166 / 7%), transparent 34%),
            #f3f6fb;
    }

    .reset-card {
        width: min(100%, 460px);
        padding: 36px;
        border: 1px solid #e3e9f2;
        border-radius: 20px;
        background: #fff;
        box-shadow: 0 24px 60px rgb(26 40 72 / 12%);
    }

    .reset-mark {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        margin-bottom: 22px;
        border-radius: 14px;
        background: #edf3ff;
        color: #2456a6;
    }

    .reset-mark svg { width: 25px; height: 25px; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .reset-eyebrow { margin: 0 0 8px; color: #2a5ab4; font-size: 0.76rem; font-weight: 750; letter-spacing: 0.08em; text-transform: uppercase; }
    .reset-header h1 { margin: 0; font-size: clamp(1.55rem, 5vw, 1.85rem); line-height: 1.2; letter-spacing: -0.035em; }
    .reset-intro { margin: 10px 0 22px; color: #5a6780; line-height: 1.55; }

    .password-guidance {
        display: flex;
        gap: 11px;
        align-items: flex-start;
        margin-bottom: 24px;
        padding: 13px 14px;
        border: 1px solid #e2eaff;
        border-radius: 12px;
        background: #f7f9ff;
        color: #455675;
        font-size: 0.88rem;
        line-height: 1.5;
    }

    .password-guidance p { margin: 0; }
    .password-guidance strong { color: #253e70; }
    .guidance-dot { width: 8px; height: 8px; flex: 0 0 8px; margin-top: 6px; border-radius: 50%; background: #3970c5; }

    .reset-field { margin-bottom: 18px; }
    .reset-field label { display: block; margin-bottom: 8px; color: #25314a; font-size: 0.92rem; font-weight: 650; }
    .reset-input {
        width: 100%;
        min-height: 52px;
        padding: 12px 14px;
        border: 1px solid #c6cfdd;
        border-radius: 10px;
        background: #fbfcff;
        color: #172033;
        font: inherit;
        transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
    }

    .reset-input::placeholder { color: #9aa5b7; }
    .reset-input:focus { outline: none; border-color: #3970c5; background: #fff; box-shadow: 0 0 0 4px rgb(57 112 197 / 13%); }
    .reset-error { margin: 8px 0 0; color: #b42318; font-size: 0.88rem; line-height: 1.45; }

    .reset-submit {
        width: 100%;
        min-height: 50px;
        margin-top: 4px;
        border: 0;
        border-radius: 10px;
        background: #2456a6;
        color: #fff;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
        transition: background-color 150ms ease, transform 150ms ease, opacity 150ms ease;
    }

    .reset-submit:hover:not(:disabled) { background: #1d478f; }
    .reset-submit:active:not(:disabled) { transform: translateY(1px); }
    .reset-submit:focus-visible { outline: 3px solid #91b7ff; outline-offset: 3px; }
    .reset-submit:disabled { cursor: wait; opacity: 0.72; }

    @media (max-width: 480px) {
        .reset-card { padding: 28px 22px; }
    }
</style>
