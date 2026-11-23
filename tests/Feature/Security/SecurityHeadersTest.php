<?php

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
