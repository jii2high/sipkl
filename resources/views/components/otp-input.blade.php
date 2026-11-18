{{--
    Input OTP 6 kotak mono dengan auto-advance dan dukungan paste (DESIGN.md "Input & Formulir").
    Nilai akhir disalin ke satu input tersembunyi yang terikat `wire:model`, jadi aturan
    validasi/Livewire tetap memakai satu properti.
--}}
@props(['length' => 6])

<div
    x-data="{
        digits: Array(@js($length)).fill(''),
        get code() { return this.digits.join(''); },
        focusBox(i) { (this.$refs.boxes[i] ?? null)?.focus(); },
        commit() {
            this.$refs.hidden.value = this.code;
            this.$refs.hidden.dispatchEvent(new Event('input', { bubbles: true }));
        },
        onInput(i, event) {
            const box = event.target;
            box.value = (box.value.match(/\d/g) ?? []).slice(-1).join('');
            this.digits[i] = box.value;
            this.commit();
            if (box.value && i < @js($length - 1)) this.focusBox(i + 1);
        },
        onKeydown(i, event) {
            if (event.key === 'Backspace' && ! event.target.value && i > 0) this.focusBox(i - 1);
        },
        onPaste(event) {
            const text = (event.clipboardData?.getData('text') ?? '').replace(/\D/g, '').slice(0, @js($length));
            if (! text) return;
            event.preventDefault();
            this.digits = Array(@js($length)).fill('').map((_, j) => text[j] ?? '');
            this.$refs.boxes.forEach((box, j) => { box.value = this.digits[j]; });
            this.commit();
            this.focusBox(Math.min(text.length, @js($length - 1)));
        },
        reset() { this.digits = Array(@js($length)).fill(''); this.$refs.boxes.forEach((b) => { b.value = ''; }); this.$refs.hidden.value = ''; }
    }"
    x-on:otp-resent.window="reset()"
>
    <input type="hidden" x-ref="hidden" {{ $attributes->wire('model') }}>

    <div class="flex justify-between gap-2" @paste="onPaste($event)">
        <template x-for="i in @js($length)" :key="i">
            <input
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="1"
                x-ref="boxes"
                aria-label="Digit OTP"
                class="pkl-input h-12 w-10 shrink-0 text-center font-mono text-[1.25rem]"
                @input="onInput(i - 1, $event)"
                @keydown="onKeydown(i - 1, $event)"
            >
        </template>
    </div>
</div>
