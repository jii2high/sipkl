<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>{{ $title ?? 'Portal Eksternal' }}</title>
    <x-theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--bg)] text-[var(--text)]">
    {{-- Layout minimal satu tugas: satu kolom, tanpa navigasi. --}}
    <main class="mx-auto w-full max-w-[640px] px-4 py-8">
        <div class="pkl-card">
            {{ $slot }}
        </div>

        <div class="mt-4 flex justify-center">
            <x-theme-switcher />
        </div>
    </main>
</body>
</html>
