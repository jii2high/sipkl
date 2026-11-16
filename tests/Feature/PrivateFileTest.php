<?php

use App\Models\User;
use App\Policies\PrivateFilePolicy;
use App\Services\PrivateFileStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('private');
    PrivateFilePolicy::reset();
});

function uploadPdf(string $name = 'laporan.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF",
    );
}

it('menyimpan file private dengan pola {periode}/{profil}/{uuid}.{ext}', function () {
    $path = app(PrivateFileStore::class)->put(uploadPdf(), 'laporan', 3);

    expect($path)->toMatch('#^3/laporan/[0-9a-f-]{36}\.pdf$#');
    Storage::disk('private')->assertExists($path);
});

it('mengunduh file bila policy mengizinkan', function () {
    $user = User::factory()->create();
    $path = app(PrivateFileStore::class)->put(uploadPdf(), 'laporan', 3);

    PrivateFilePolicy::register('3/laporan/', fn (User $u, string $p) => $u->id === $user->id);

    $this->actingAs($user)->get('/files/'.$path)
        ->assertOk()
        ->assertDownload();
});

it('menolak unduhan bila policy menolak', function () {
    $pemilik = User::factory()->create();
    $lain = User::factory()->create();
    $path = app(PrivateFileStore::class)->put(uploadPdf(), 'laporan', 3);

    PrivateFilePolicy::register('3/laporan/', fn (User $u) => $u->id === $pemilik->id);

    $this->actingAs($lain)->get('/files/'.$path)->assertForbidden();
});

it('menolak unduhan bila belum ada policy untuk prefix tersebut', function () {
    $user = User::factory()->create();
    $path = app(PrivateFileStore::class)->put(uploadPdf(), 'laporan', 3);

    $this->actingAs($user)->get('/files/'.$path)->assertForbidden();
});

it('menolak tamu yang belum login', function () {
    $path = app(PrivateFileStore::class)->put(uploadPdf(), 'laporan', 3);

    $this->get('/files/'.$path)->assertRedirect('/login');
});

it('menolak path traversal dan path di luar pola', function () {
    $user = User::factory()->create();
    PrivateFilePolicy::register('3/', fn () => true);

    $this->actingAs($user)->get('/files/3/laporan/../../../.env')->assertNotFound();
    $this->actingAs($user)->get('/files//etc/passwd')->assertNotFound();
});

it('tidak mengekspos file private lewat symlink publik', function () {
    expect(is_link(public_path('storage')))->toBeFalse()
        ->and(str_contains(storage_path('app/private'), DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR))->toBeFalse();
});
