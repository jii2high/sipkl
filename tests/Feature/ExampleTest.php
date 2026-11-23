<?php

test('beranda publik dapat dibuka', function () {
    $this->get('/')->assertOk();
});

test('halaman login kustom dapat dibuka', function () {
    $this->get('/login')->assertOk();
});

test('tamu diarahkan ke /login, bukan login panel', function () {
    $this->get('/hubin')->assertRedirect('/login');
    $this->get('/kaprog')->assertRedirect('/login');
    $this->get('/guru')->assertRedirect('/login');
});

test('panel login bawaan Filament sudah tidak ada', function () {
    $this->get('/hubin/login')->assertNotFound();
    $this->get('/kaprog/login')->assertNotFound();
    $this->get('/guru/login')->assertNotFound();
});
