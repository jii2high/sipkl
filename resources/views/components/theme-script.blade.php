{{--
    Terapkan tema sebelum render pertama agar tidak ada kilatan tema salah.
    Kunci localStorage sama dengan Filament ("filament:theme:mode").
--}}
<script>
    (function () {
        var mode = localStorage.getItem('filament:theme:mode') || 'system';
        var dark = mode === 'dark'
            || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        var root = document.documentElement;
        if (dark) {
            root.classList.add('dark');
        }
        root.setAttribute('data-theme', dark ? 'dark' : 'light');
    })();
</script>
