<?php

use Illuminate\Support\Facades\Vite;

test('respons web memuat header keamanan wajib', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'self'")
        ->and($response->headers->get('Strict-Transport-Security'))->toBeNull();
});

test('rute eksternal memakai Referrer-Policy no-referrer', function () {
    $this->get('/eksternal/link')
        ->assertOk()
        ->assertHeader('Referrer-Policy', 'no-referrer');
});

test('CSP mengizinkan origin Vite saat mode hot', function () {
    $hot = tempnam(sys_get_temp_dir(), 'vite-hot');
    file_put_contents($hot, "http://[::1]:5173\n");
    Vite::useHotFile($hot);

    $csp = $this->get('/')->headers->get('Content-Security-Policy');

    unlink($hot);

    expect($csp)->toContain("default-src 'self'")
        ->and($csp)->toContain('http://[::1]:5173')
        ->and($csp)->toContain('ws://[::1]:5173');
});

test('CSP tidak mengizinkan origin Vite tanpa mode hot', function () {
    Vite::useHotFile(sys_get_temp_dir().'/vite-hot-tidak-ada');

    expect($this->get('/')->headers->get('Content-Security-Policy'))->not->toContain('5173');
});
