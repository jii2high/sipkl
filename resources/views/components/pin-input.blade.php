{{--
    Input PIN 4 digit untuk penguji eksternal (TRD.md §9.2). Perilaku sama dengan
    komponen OTP: auto-advance, dukungan paste, satu nilai tersembunyi untuk `wire:model`.
--}}
@props(['length' => 4])

<div
    x-data="{
        length: @js($length),
        digits: Array(@js($length)).fill(''),
        boxes() { return Array.from(this.$refs.row.querySelectorAll('input[data-pin]')); },
        commit() {
            this.$refs.hidden.value = this.digits.join('');
            this.$refs.hidden.dispatchEvent(new Event('input', { bubbles: true }));
        },
        fill(value) {
            const text = (value ?? '').replace(/\D/g, '').slice(0, this.length);
            if (! text) return;
            this.digits = Array(this.length).fill('').map((_, j) => text[j] ?? '');
            this.boxes().forEach((box, j) => { box.value = this.digits[j]; });
            this.commit();
            this.boxes()[Math.min(text.length, this.length - 1)]?.focus();
        },
        onInput(index, event) {
            const box = event.target;
            const raw = box.value.replace(/\D/g, '');
            if (raw.length > 1) { this.fill(raw); return; }
            box.value = raw;
            this.digits[index] = raw;
            this.commit();
            if (raw) this.boxes()[index + 1]?.focus();
        },
        onKeydown(index, event) {
            if (event.key === 'Backspace' && ! event.target.value && index > 0) {
                this.boxes()[index - 1]?.focus();
            }
        },
        onPaste(event) {
            event.preventDefault();
            this.fill(event.clipboardData?.getData('text'));
        },
        reset() {
            this.digits = Array(this.length).fill('');
            this.boxes().forEach((box) => { box.value = ''; });
            this.$refs.hidden.value = '';
        }
    }"
    x-on:pin-reset.window="reset()"
>
    <input type="hidden" x-ref="hidden" {{ $attributes->wire('model') }}>

    <div x-ref="row" class="flex justify-center gap-2" @paste="onPaste($event)">
        <template x-for="i in length" :key="i">
            <input
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="1"
                data-pin
                aria-label="Digit PIN"
                class="pkl-input h-12 w-11 shrink-0 text-center font-mono text-[1.25rem]"
                @focus="$event.target.select()"
                @input="onInput(i - 1, $event)"
                @keydown="onKeydown(i - 1, $event)"
            >
        </template>
    </div>
</div>