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

                {{-- Enam kotak terpisah. Digit hanya disimpan di Alpine selama mengetik,
                     lalu disinkronkan ke properti Livewire `code` (deferred, tanpa request
                     per ketikan). Jadi fokus tidak pernah hilang ke luar kotak. --}}
                <div
                    class="otp-boxes"
                    role="group"
                    aria-labelledby="otp-label"
                    x-data="{
                        code: @entangle('code'),
                        digits: ['', '', '', '', '', ''],
                        init() {
                            const source = this.code === null || this.code === undefined ? '' : String(this.code);
                            const chars = source.replace(/[^0-9]/g, '').slice(0, 6).split('');

                            this.digits = Array.from({ length: 6 }, function (_, index) {
                                return chars[index] === undefined ? '' : chars[index];
                            });

                            this.$nextTick(() => {
                                const firstEmpty = this.digits.findIndex((digit) => digit === '');
                                this.focus(firstEmpty === -1 ? 5 : firstEmpty);
                            });
                        },
                        sync() {
                            this.code = this.digits.join('');
                        },
                        apply(index, raw) {
                            const chars = String(raw === null || raw === undefined ? '' : raw)
                                .replace(/[^0-9]/g, '')
                                .split('');

                            if (chars.length === 0) {
                                this.digits[index] = '';
                                this.sync();

                                return;
                            }

                            let cursor = index;

                            for (let i = 0; i < chars.length && cursor < 6; i++, cursor++) {
                                this.digits[cursor] = chars[i];
                            }

                            this.sync();
                            this.$nextTick(() => this.focus(Math.min(cursor, 5)));
                        },
                        onInput(index, event) {
                            this.apply(index, event.target.value);
                            event.target.value = this.digits[index] === undefined ? '' : this.digits[index];
                        },
                        onBackspace(index, event) {
                            if (event.target.value !== '' || index === 0) {
                                return;
                            }

                            event.preventDefault();
                            this.digits[index - 1] = '';
                            this.sync();
                            this.$nextTick(() => this.focus(index - 1));
                        },
                        onPaste(event) {
                            const text = event.clipboardData ? event.clipboardData.getData('text') : '';

                            if (!/[0-9]/.test(text)) {
                                return;
                            }

                            event.preventDefault();
                            this.apply(0, text);
                        },
                        focus(index) {
                            const box = this.$el.querySelectorAll('.otp-digit')[index];

                            if (box) {
                                box.focus();
                                box.select();
                            }
                        }
                    }"
                    x-on:paste="onPaste($event)"
                >
                    <span id="otp-label" class="visually-hidden">Kode OTP 6 digit</span>

                    <template x-for="(digit, index) in digits" :key="index">
                        <input
                            type="text"
                            class="otp-digit"
                            :class="{ 'is-filled': digit !== '' }"
                            :id="index === 0 ? 'otp-code' : 'otp-code-' + index"
                            :value="digit"
                            :aria-label="'Digit ' + (index + 1) + ' dari 6'"
                            :autocomplete="index === 0 ? 'one-time-code' : 'off'"
                            inputmode="numeric"
                            autocapitalize="off"
                            spellcheck="false"
                            x-on:input="onInput(index, $event)"
                            x-on:keydown.backspace="onBackspace(index, $event)"
                            x-on:focus="$event.target.select()"
                        >
                    </template>
                </div>

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
    .visually-hidden {
        position: absolute;
        width: 1px;
        height: 1px;
        margin: -1px;
        padding: 0;
        overflow: hidden;
        clip: rect(0 0 0 0);
        white-space: nowrap;
        border: 0;
    }
    .otp-boxes {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 8px;
        margin: 0 auto;
        max-width: 330px;
    }
    .otp-digit {
        box-sizing: border-box;
        width: 100%;
        height: 60px;
        padding: 0;
        border: 1px solid #b7bfd0;
        border-radius: 12px;
        background: #fff;
        color: #172033;
        font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
        font-size: 1.45rem;
        font-weight: 700;
        text-align: center;
        caret-color: #2456a6;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
    }
    .otp-digit.is-filled { border-color: #6f8fc9; background: #f7f9ff; }
    .otp-digit:focus { outline: none; border-color: #2456a6; box-shadow: 0 0 0 4px rgb(36 86 166 / 15%); }
    .otp-digit::selection { background: #cfe0ff; }
    button { width: 100%; min-height: 46px; border: none; border-radius: 8px; background: #2456a6; color: white; font-weight: 700; cursor: pointer; }
    button:disabled { cursor: wait; opacity: 0.72; }
    .secondary { background: #edf2ff; color: #1d3d7a; margin-top: 12px; }
    .error { margin-top: 8px; color: #c33131; }
    .success { margin: 0 0 16px; padding: 10px 12px; border-radius: 8px; background: #edf9ef; color: #175a28; }
    .cooldown { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 10px; padding: 11px 13px; border: 1px solid #dce6fa; border-radius: 10px; background: #f3f6ff; color: #274575; font-size: 0.9rem; }
    .cooldown strong { color: #1d3d7a; font-variant-numeric: tabular-nums; font-size: 1rem; }
    @media (max-width: 380px) { .auth-card { padding: 24px 18px; } .otp-boxes { gap: 6px; } .otp-digit { height: 54px; font-size: 1.25rem; } }
</style>
