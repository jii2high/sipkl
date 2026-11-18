{{--
    Pilihan tema di panel. Memakai komponen bawaan Filament agar integrasi dark mode
    dan penyimpanan preferensi konsisten dengan perilaku Filament. Skrip kecil di
    bawah menyimpan pilihan ke akun (`users.theme_preference`, T1.1) dan memakai
    preferensi tersimpan saat pertama kali panel dibuka.
--}}
@php
    $savedTheme = filament()->auth()->user()?->theme_preference?->value;
@endphp

<div
    data-theme-pref="{{ $savedTheme }}"
    data-theme-endpoint="{{ route('theme.update') }}"
    x-data="{
        init() {
            const saved = $el.dataset.themePref;
            if (saved && ! localStorage.getItem('sim-pkl-theme-applied')) {
                localStorage.setItem('filament:theme:mode', saved);
                document.documentElement.classList.toggle('dark', saved === 'dark');
                localStorage.setItem('sim-pkl-theme-applied', '1');
            }
            new MutationObserver(() => {
                const mode = localStorage.getItem('filament:theme:mode') || 'system';
                const token = decodeURIComponent(
                    (document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/) || [])[1] || ''
                );
                fetch($el.dataset.themeEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ theme: mode }),
                });
            }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        },
    }"
></div>

<x-filament-panels::theme-switcher />
