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
    <button type="button" data-theme-option="light" aria-pressed="false" aria-label="Tema terang" title="Tema terang"
        class="inline-flex h-11 w-11 items-center justify-center rounded-md text-[var(--text-muted)]">
        <x-heroicon-o-sun class="h-5 w-5" aria-hidden="true" />
    </button>
    <button type="button" data-theme-option="dark" aria-pressed="false" aria-label="Tema gelap" title="Tema gelap"
        class="inline-flex h-11 w-11 items-center justify-center rounded-md text-[var(--text-muted)]">
        <x-heroicon-o-moon class="h-5 w-5" aria-hidden="true" />
    </button>
    <button type="button" data-theme-option="system" aria-pressed="false" aria-label="Ikuti tema sistem" title="Ikuti tema sistem"
        class="inline-flex h-11 w-11 items-center justify-center rounded-md text-[var(--text-muted)]">
        <x-heroicon-o-computer-desktop class="h-5 w-5" aria-hidden="true" />
    </button>
</div>
