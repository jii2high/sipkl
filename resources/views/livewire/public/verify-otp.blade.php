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
                    class="otp-input"
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

            <button type="submit" wire:loading.attr="disabled" wire:target="submit">
                <span wire:loading.remove wire:target="submit">Verifikasi</span>
                <span wire:loading wire:target="submit">Memverifikasi...</span>
            </button>
        </form>

        <div class="actions">
            <button
                type="button"
                class="secondary"
                wire:click="resend"
                wire:loading.attr="disabled"
                wire:target="resend"
            >
                <span wire:loading.remove wire:target="resend">Kirim ulang OTP</span>
                <span wire:loading wire:target="resend">Mengirim ulang...</span>
            </button>
            @error('resend')
                <p class="error" role="alert">{{ $message }}</p>
            @enderror
            @if ($resendCooldownSeconds > 0)
                <div
                    class="cooldown"
                    role="status"
                    data-otp-cooldown
                    x-data="{ remaining: {{ $resendCooldownSeconds }} }"
                    x-init="const timer = setInterval(() => { if (!$el.isConnected || remaining <= 0) { clearInterval(timer); return; } remaining -= 1; }, 1000)"
                >
                    <span>Coba kirim ulang dalam</span>
                    <strong x-text="`${String(Math.floor(remaining / 60)).padStart(2, '0')}:${String(remaining % 60).padStart(2, '0')}`"></strong>
                </div>
            @endif
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
    .otp-input {
        display: block;
        box-sizing: border-box;
        width: 100%;
        max-width: 330px;
        min-height: 64px;
        margin: 0 auto;
        padding: 10px 12px;
        border: 1px solid #b7bfd0;
        border-radius: 12px;
        background-color: #fff;
        background-image: linear-gradient(to right, transparent calc(100% - 1px), #dce3ef calc(100% - 1px));
        background-size: calc(100% / 6) 100%;
        color: #172033;
        font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
        font-size: 1.65rem;
        font-weight: 700;
        letter-spacing: 0.8rem;
        text-align: center;
    }
    .otp-input:focus-visible { outline: 3px solid #91b7ff; outline-offset: 2px; border-color: #2456a6; }
    button { width: 100%; min-height: 46px; border: none; border-radius: 8px; background: #2456a6; color: white; font-weight: 700; cursor: pointer; }
    button:disabled { cursor: wait; opacity: 0.72; }
    .secondary { background: #edf2ff; color: #1d3d7a; margin-top: 12px; }
    .error { margin-top: 8px; color: #c33131; }
    .success { margin: 0 0 16px; padding: 10px 12px; border-radius: 8px; background: #edf9ef; color: #175a28; }
    .cooldown { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 10px; padding: 11px 13px; border: 1px solid #dce6fa; border-radius: 10px; background: #f3f6ff; color: #274575; font-size: 0.9rem; }
    .cooldown strong { color: #1d3d7a; font-variant-numeric: tabular-nums; font-size: 1rem; }
    @media (max-width: 380px) { .auth-card { padding: 24px 18px; } }
</style>
