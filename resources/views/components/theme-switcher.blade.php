{{--
    Pemilih tema Terang/Gelap/Ikuti sistem untuk halaman publik dan portal eksternal.
    Panel Filament memakai komponen bawaan Filament dengan kunci localStorage yang sama.
--}}
<div
    class="inline-flex items-center gap-1 rounded-lg border border-[var(--border)] p-1"
    role="group"
    aria-label="Pilihan tema"
    data-theme-switcher
>
    <button type="button" data-theme-option="light" aria-pressed="false"
        class="rounded-md px-2 py-1 text-[0.8125rem] text-[var(--text-muted)]">Terang</button>
    <button type="button" data-theme-option="dark" aria-pressed="false"
        class="rounded-md px-2 py-1 text-[0.8125rem] text-[var(--text-muted)]">Gelap</button>
    <button type="button" data-theme-option="system" aria-pressed="false"
        class="rounded-md px-2 py-1 text-[0.8125rem] text-[var(--text-muted)]">Ikuti sistem</button>
</div>
