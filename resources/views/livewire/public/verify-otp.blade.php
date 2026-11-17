<main class="auth-page">
    <section class="auth-card" aria-labelledby="otp-title">
        <p class="eyebrow">Verifikasi akun</p>
        <h1 id="otp-title">Masukkan kode OTP</h1>
        <p class="intro">Kode OTP dikirim ke email sekolah Anda. Kode berlaku 60 detik.</p>

        @if ($message)
            <p class="success" role="status">{{ $message }}</p>
        @endif

        <form wire:submit="submit" novalidate>
            <div class="field">
                <label for="otp-code">Kode OTP</label>
                <input
                    id="otp-code"
                    type="text"
                    inputmode="numeric"
                    maxlength="6"
                    wire:model="code"
                    autocomplete="one-time-code"
                    required
                >
                @error('code')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit">Verifikasi</button>
        </form>

        <div class="actions">
            <button type="button" class="secondary" wire:click="resend">Kirim ulang OTP</button>
        </div>
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
    .secondary { background: #edf2ff; color: #1d3d7a; margin-top: 12px; }
    .error { margin-top: 8px; color: #c33131; }
    .success { margin: 0 0 16px; padding: 10px 12px; border-radius: 8px; background: #edf9ef; color: #175a28; }
    @media (max-width: 380px) { .auth-card { padding: 24px 18px; } }
</style>
