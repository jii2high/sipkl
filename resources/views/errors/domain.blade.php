@php
    $judul = match ((int) $status) {
        409 => 'Status sudah berubah',
        422 => 'Permintaan tidak dapat diproses',
        default => 'Permintaan tidak dapat diproses',
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul }} — {{ config('pkl.school.name') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            background: #f8fafc;
            color: #0f172a;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .kartu {
            max-width: 32rem;
            width: 100%;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
        }
        .kode { font-size: 0.875rem; font-weight: 600; color: #64748b; }
        h1 { font-size: 1.25rem; margin: 0.25rem 0 0.75rem; }
        p { margin: 0 0 1.25rem; line-height: 1.6; }
        a {
            display: inline-block;
            padding: 0.625rem 1rem;
            border-radius: 0.5rem;
            background: #1d4ed8;
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
        }
        @media (prefers-color-scheme: dark) {
            body { background: #0f172a; color: #e2e8f0; }
            .kartu { background: #1e293b; border-color: #334155; }
            a { background: #3b82f6; }
        }
    </style>
</head>
<body>
    <main class="kartu">
        <p class="kode">Galat {{ $status }}</p>
        <h1>{{ $judul }}</h1>
        <p>{{ $message }}</p>
        <a href="{{ url('/') }}">
            <x-heroicon-o-arrow-left width="16" height="16" aria-hidden="true" style="vertical-align: -0.125em; margin-right: 0.375rem" />
            Kembali ke beranda
        </a>
    </main>
</body>
</html>
