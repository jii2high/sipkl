// Tema publik dan portal eksternal memakai kunci localStorage yang sama dengan
// Filament ("filament:theme:mode") agar pilihan tema konsisten di seluruh aplikasi.
(function () {
    const STORAGE_KEY = 'filament:theme:mode';

    function prefersDark() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function apply(mode) {
        localStorage.setItem(STORAGE_KEY, mode);

        const dark = mode === 'dark' || (mode === 'system' && prefersDark());

        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
    }

    function current() {
        return localStorage.getItem(STORAGE_KEY) || 'system';
    }

    function syncSwitchers() {
        document.querySelectorAll('[data-theme-switcher] button[data-theme-option]').forEach((button) => {
            const active = button.dataset.themeOption === current();

            button.setAttribute('aria-pressed', active ? 'true' : 'false');
            button.classList.toggle('bg-[var(--primary-soft)]', active);
            button.classList.toggle('text-[var(--primary)]', active);
            button.classList.toggle('font-medium', active);
            button.classList.toggle('text-[var(--text-muted)]', !active);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-theme-switcher] button[data-theme-option]').forEach((button) => {
            button.addEventListener('click', () => {
                apply(button.dataset.themeOption);
                syncSwitchers();
            });
        });

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (current() === 'system') {
                apply('system');
                syncSwitchers();
            }
        });

        syncSwitchers();
    });
})();
