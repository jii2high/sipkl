<main class="login-page">
    <section class="login-card" aria-labelledby="login-title">
        <p class="eyebrow">Sistem Informasi Manajemen PKL</p>
        <h1 id="login-title">Masuk SIPKL</h1>
        <p class="intro">Gunakan email sekolah dan password akun Anda.</p>

        @if ($status)
            <p class="message success" role="status">{{ $status }}</p>
        @endif

        <form wire:submit="submit" novalidate>
            <div class="field">
                <label for="email">Email sekolah</label>
                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    autocomplete="username"
                    inputmode="email"
                    required
                    aria-describedby="email-error"
                >
                @error('email')
                    <p id="email-error" class="message error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input
                    id="password"
                    type="password"
                    wire:model="password"
                    autocomplete="current-password"
                    required
                    aria-describedby="password-error credentials-error"
                >
                @error('password')
                    <p id="password-error" class="message error">{{ $message }}</p>
                @enderror
            </div>

            @error('credentials')
                <p id="credentials-error" class="message error" role="alert">{{ $message }}</p>
            @enderror

            <button type="submit" wire:loading.attr="disabled" wire:target="submit">
                <span wire:loading.remove wire:target="submit">Masuk</span>
                <span wire:loading wire:target="submit">Memeriksa...</span>
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

    body { margin: 0; min-width: 320px; }

    .login-page {
        min-height: 100vh;
        display: grid;
        place-items: center;
        padding: 24px 16px;
    }

    .login-card {
        width: min(100%, 420px);
        padding: 32px;
        background: #fff;
        border: 1px solid #e1e7f0;
        border-radius: 16px;
        box-shadow: 0 16px 40px rgb(22 39 69 / 8%);
    }

    .eyebrow {
        margin: 0 0 8px;
        color: #2857a4;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    h1 { margin: 0; font-size: 1.75rem; }

    .intro { margin: 8px 0 24px; color: #526078; }

    .field { margin-bottom: 18px; }

    label { display: block; margin-bottom: 7px; font-weight: 600; }

    input {
        width: 100%;
        min-height: 46px;
        padding: 10px 12px;
        border: 1px solid #aeb9ca;
        border-radius: 8px;
        background: #fff;
        color: inherit;
        font: inherit;
    }

    input:focus-visible, button:focus-visible {
        outline: 3px solid #91b7ff;
        outline-offset: 2px;
    }

    button {
        width: 100%;
        min-height: 48px;
        border: 0;
        border-radius: 8px;
        background: #2456a6;
        color: #fff;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }

    button:disabled { cursor: wait; opacity: 0.7; }

    .message { margin: 8px 0 0; font-size: 0.9rem; }

    .error { color: #a32121; }

    .success {
        margin: 0 0 18px;
        padding: 12px;
        border-radius: 8px;
        background: #edf6ef;
        color: #205b2b;
    }

    @media (max-width: 380px) {
        .login-card { padding: 24px 20px; }
        h1 { font-size: 1.5rem; }
    }
</style>
