<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan respons (TRD.md A16). CSP dilonggarkan seperlunya agar
 * Livewire/Alpine dan Filament tetap berjalan: skrip/inline style wajib, eval
 * untuk Alpine, dan `data:`/`blob:` untuk berkas yang digambar di klien.
 * Saat `composer dev` berjalan, origin Vite ditambahkan supaya CSP tidak
 * memblokir aset dev yang disajikan dari port berbeda.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $origins = $this->viteDevOrigins();
        $http = $origins === [] ? '' : ' '.implode(' ', $origins);
        $ws = $origins === [] ? '' : ' '.implode(' ', array_map(
            fn (string $origin): string => preg_replace('/^http/', 'ws', $origin) ?? $origin,
            $origins,
        ));

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => $request->is('eksternal/*') ? 'no-referrer' : 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'".$http,
                "style-src 'self' 'unsafe-inline'".$http,
                "img-src 'self' data: blob:".$http,
                "font-src 'self' data:".$http,
                "connect-src 'self'".$http.$ws,
                "media-src 'self' blob:",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'self'",
            ]),
        ];

        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }

    /**
     * Origin server Vite saat mode hot. Hanya skema/host/port yang dipakai;
     * isi berkas tidak boleh masuk header mentah-mentah.
     *
     * @return array<int, string>
     */
    private function viteDevOrigins(): array
    {
        if (! Vite::isRunningHot()) {
            return [];
        }

        $parts = parse_url(trim((string) file_get_contents(Vite::hotFile())));

        if (! in_array($parts['scheme'] ?? null, ['http', 'https'], true) || ! isset($parts['host'])) {
            return [];
        }

        return [$parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '')];
    }
}
