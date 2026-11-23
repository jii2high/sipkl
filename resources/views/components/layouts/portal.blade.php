{{--
    Layout portal mobile (siswa, walas, kepsek): satu kolom, lebar maks 420px,
    target sentuh >= 44px, token DESIGN.md. Tanpa Filament.
--}}
@php
    $portalRole = (string) session('active_role');
    $portalNav = collect(match ($portalRole) {
        'siswa' => [
            ['route' => 'portal.siswa', 'label' => 'pkl.portal.nav.pengajuan'],
            ['route' => 'portal.siswa.bimbingan', 'label' => 'pkl.portal.nav.bimbingan'],
            ['route' => 'portal.siswa.jadwal-sidang', 'label' => 'pkl.portal.nav.jadwal_sidang'],
            ['route' => 'portal.notifikasi', 'label' => 'pkl.portal.nav.notifikasi'],
        ],
        'walas' => [
            ['route' => 'portal.walas', 'label' => 'pkl.portal.nav.antrean'],
            ['route' => 'portal.walas.kanban', 'label' => 'pkl.portal.nav.kanban'],
            ['route' => 'portal.notifikasi', 'label' => 'pkl.portal.nav.notifikasi'],
        ],
        'kepsek' => [
            ['route' => 'portal.kepsek', 'label' => 'pkl.portal.nav.pengesahan'],
            ['route' => 'portal.notifikasi', 'label' => 'pkl.portal.nav.notifikasi'],
        ],
        default => [],
    })->filter(fn (array $item): bool => Route::has($item['route']))->values();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? ('Portal SIM PKL ' . config('pkl.school.name')) }}</title>
    <x-theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[var(--bg)] text-[var(--text)]">
    <header class="sticky top-0 z-10 border-b border-[var(--border)] bg-[var(--surface)]">
        <div class="mx-auto flex min-h-[56px] w-full max-w-[420px] items-center justify-between gap-2 px-4 py-2">
            <p class="text-[0.9375rem] font-semibold">{{ __('pkl.portal.judul') }}</p>

            <div class="flex items-center gap-1">
                @if (Route::has('portal.notifikasi'))
                    <a
                        href="{{ route('portal.notifikasi') }}"
                        class="flex h-11 w-11 items-center justify-center rounded-[var(--radius-md)] text-[var(--primary)] focus:outline-none focus:ring-2 focus:ring-[var(--focus-ring)]"
                        aria-label="{{ __('pkl.portal.notifikasi') }}"
                    >
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                    </a>
                @endif

                @livewire(\App\Livewire\Support\RoleSwitcher::class)
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-[420px] px-4 py-4 pb-28">
        {{ $slot }}
    </main>

    @if ($portalNav->isNotEmpty())
        <nav class="fixed inset-x-0 bottom-0 border-t border-[var(--border)] bg-[var(--surface)]" aria-label="{{ __('pkl.portal.judul') }}">
            <ul class="mx-auto flex w-full max-w-[420px]">
                @foreach ($portalNav as $item)
                    <li class="flex-1">
                        <a
                            href="{{ route($item['route']) }}"
                            @if (request()->routeIs($item['route'])) aria-current="page" @endif
                            class="flex min-h-[44px] w-full flex-col items-center justify-center px-1 py-2 text-[0.75rem] font-medium {{ request()->routeIs($item['route']) ? 'text-[var(--primary)]' : 'text-[var(--text-muted)]' }} focus:outline-none focus:ring-2 focus:ring-[var(--focus-ring)]"
                        >
                            {{ __($item['label']) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    @livewireScripts
</body>
</html>