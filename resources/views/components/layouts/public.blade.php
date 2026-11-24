<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('pkl.school.name') }}</title>
    <x-theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--bg)] text-[var(--text)]">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-[var(--surface)] focus:px-4 focus:py-2">
        Lompat ke konten
    </a>

    <header class="border-b border-[var(--border)] bg-[var(--surface)]">
        <div class="mx-auto flex max-w-[960px] items-center justify-between gap-4 px-4 py-3">
            <a href="{{ url('/') }}" class="min-w-0 truncate text-[1.125rem] font-semibold text-[var(--text)]">
                <span class="hidden sm:inline">SIM PKL </span>{{ config('pkl.school.name') }}
            </a>
            <nav class="flex shrink-0 items-center gap-3 text-[0.875rem]" aria-label="Navigasi publik">
                <a href="{{ route('faq') }}" class="inline-flex items-center gap-1 rounded-[var(--radius-sm)] px-1 py-2 text-[var(--primary)] hover:underline focus:outline-none focus:ring-2 focus:ring-[var(--focus-ring)]">
                    <x-heroicon-o-question-mark-circle class="h-5 w-5" aria-hidden="true" />
                    FAQ
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1 rounded-[var(--radius-sm)] px-1 py-2 text-[var(--primary)] hover:underline focus:outline-none focus:ring-2 focus:ring-[var(--focus-ring)]">
                    <x-heroicon-o-arrow-right-end-on-rectangle class="h-5 w-5" aria-hidden="true" />
                    Masuk
                </a>
                <x-theme-switcher />
            </nav>
        </div>
    </header>

    <main id="konten" class="mx-auto max-w-[960px] px-4 py-8">
        {{ $slot }}
    </main>

    <footer class="border-t border-[var(--border)] py-6">
        <div class="mx-auto max-w-[960px] px-4 text-[0.8125rem] text-[var(--text-muted)]">
            {{ config('pkl.school.name') }}
        </div>
    </footer>
</body>
</html>
