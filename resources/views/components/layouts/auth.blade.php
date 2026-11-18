{{--
    Layout halaman auth (login, OTP, ganti kata sandi): satu kolom, lebar maks 480px,
    terpusat. Mengikuti DESIGN.md "Portal eksternal/Halaman publik" dan token tema.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? ('Masuk — SIM PKL ' . config('pkl.school.name')) }}</title>
    <x-theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[var(--bg)] text-[var(--text)]">
    <main class="mx-auto flex min-h-screen w-full max-w-[480px] flex-col justify-center gap-6 px-4 py-8">
        <p class="text-center text-[1.125rem] font-semibold">
            SIM PKL {{ config('pkl.school.name') }}
        </p>

        <section class="pkl-card">
            {{ $slot }}
        </section>

        <div class="flex justify-center">
            <x-theme-switcher />
        </div>
    </main>

    @livewireScripts
</body>
</html>
