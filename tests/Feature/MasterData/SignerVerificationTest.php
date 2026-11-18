<?php

use App\Actions\Company\SendSignerVerificationAction;
use App\Filament\Hubin\Resources\Companies\Pages\CreateCompany;
use App\Mail\SignerVerificationMail;
use App\Models\Company;
use App\Models\User;
use App\Models\UserRole;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

function loginSignerSebagai(string $role): User
{
    $user = User::factory()->create();
    UserRole::create(['user_id' => $user->id, 'role' => $role]);

    test()->actingAs($user)->withSession(['active_role' => $role]);

    return $user;
}

beforeEach(function () {
    Filament::setCurrentPanel('hubin');
});

it('menandai signer tanpa verifikasi sebagai belum siap', function () {
    $company = Company::factory()->create([
        'pembimbing_industri_nama' => 'Budi Santoso',
        'pembimbing_industri_email' => 'budi@contoh.co.id',
    ]);

    expect($company->signerPembimbingSiap())->toBeFalse()
        ->and($company->signerKepalaSiap())->toBeFalse();

    $company->forceFill(['pembimbing_industri_email_verified_at' => now()])->save();

    expect($company->fresh()->signerPembimbingSiap())->toBeTrue();
});

it('mengirim email verifikasi berisi signed URL dan mencatat audit', function () {
    Mail::fake();

    $hubin = loginSignerSebagai('hubin');

    $company = Company::factory()->create([
        'pembimbing_industri_nama' => 'Budi Santoso',
        'pembimbing_industri_email' => 'budi@contoh.co.id',
    ]);

    app(SendSignerVerificationAction::class)->execute(
        $company,
        SendSignerVerificationAction::TIPE_PEMBIMBING,
        $hubin,
    );

    $url = null;

    Mail::assertSent(SignerVerificationMail::class, function (SignerVerificationMail $mail) use (&$url): bool {
        $url = $mail->url;

        return $mail->hasTo('budi@contoh.co.id')
            && $mail->tipe === SendSignerVerificationAction::TIPE_PEMBIMBING;
    });

    expect($url)->toContain('signature=');

    $log = DB::table('activity_log')->where('description', 'company.signer_verification_sent')->sole();

    expect($log->subject_id)->toBe($company->id)
        ->and(json_decode($log->properties, true)['tipe'])->toBe('pembimbing');
});

it('mengisi verified_at saat tautan verifikasi dibuka', function () {
    Mail::fake();

    loginSignerSebagai('hubin');

    $company = Company::factory()->create([
        'pembimbing_industri_nama' => 'Budi Santoso',
        'pembimbing_industri_email' => 'budi@contoh.co.id',
    ]);

    app(SendSignerVerificationAction::class)->execute($company, SendSignerVerificationAction::TIPE_PEMBIMBING);

    $url = null;
    Mail::assertSent(SignerVerificationMail::class, function (SignerVerificationMail $mail) use (&$url): bool {
        $url = $mail->url;

        return true;
    });

    $this->get($url)
        ->assertOk()
        ->assertSee('Email berhasil diverifikasi')
        ->assertSee('Budi Santoso');

    expect($company->fresh()->pembimbing_industri_email_verified_at)->not->toBeNull();
});

it('menolak nama signer yang generik', function () {
    Mail::fake();

    $company = Company::factory()->create([
        'pembimbing_industri_nama' => 'Pembimbing Industri',
        'pembimbing_industri_email' => 'budi@contoh.co.id',
    ]);

    expect(fn () => app(SendSignerVerificationAction::class)->execute(
        $company,
        SendSignerVerificationAction::TIPE_PEMBIMBING,
    ))->toThrow(ValidationException::class);

    Mail::assertNothingSent();
});

it('mengosongkan verifikasi saat email atau nama signer berubah', function () {
    $company = Company::factory()->signerPembimbingTerverifikasi()->create();

    expect($company->signerPembimbingSiap())->toBeTrue();

    $company->update(['pembimbing_industri_email' => 'baru@contoh.co.id']);

    expect($company->fresh()->pembimbing_industri_email_verified_at)->toBeNull();

    $company->forceFill(['pembimbing_industri_email_verified_at' => now()])->save();
    $company->update(['pembimbing_industri_nama' => 'Budi Santoso Baru']);

    expect($company->fresh()->pembimbing_industri_email_verified_at)->toBeNull();
});

it('menolak tautan lama setelah email signer diganti', function () {
    Mail::fake();

    loginSignerSebagai('hubin');

    $company = Company::factory()->create([
        'pembimbing_industri_nama' => 'Budi Santoso',
        'pembimbing_industri_email' => 'budi@contoh.co.id',
    ]);

    app(SendSignerVerificationAction::class)->execute($company, SendSignerVerificationAction::TIPE_PEMBIMBING);

    $url = null;
    Mail::assertSent(SignerVerificationMail::class, function (SignerVerificationMail $mail) use (&$url): bool {
        $url = $mail->url;

        return true;
    });

    $company->update(['pembimbing_industri_email' => 'baru@contoh.co.id']);

    $this->get($url)->assertStatus(410);
});

it('menolak tautan verifikasi yang kedaluwarsa', function () {
    $company = Company::factory()->create([
        'pembimbing_industri_nama' => 'Budi Santoso',
        'pembimbing_industri_email' => 'budi@contoh.co.id',
    ]);

    $url = URL::temporarySignedRoute(
        'signer.verifikasi',
        now()->subMinute(),
        [
            'company' => $company->id,
            'tipe' => 'pembimbing',
            'email' => hash('sha256', SendSignerVerificationAction::kunciEmail('budi@contoh.co.id')),
        ],
    );

    $this->get($url)->assertForbidden();
});

it('menolak nama signer generik lewat form perusahaan', function () {
    loginSignerSebagai('hubin');

    livewire(CreateCompany::class)
        ->fillForm([
            'nama' => 'PT Contoh',
            'alamat' => 'Jl. Contoh No. 1',
            'source' => 'hubin',
            'pembimbing_industri_nama' => 'HRD',
            'pembimbing_industri_email' => 'hrd@contoh.co.id',
        ])
        ->call('create')
        ->assertHasFormErrors(['pembimbing_industri_nama']);
});

it('menampilkan status belum siap pada halaman edit perusahaan', function () {
    loginSignerSebagai('hubin');

    $company = Company::factory()->create([
        'pembimbing_industri_nama' => 'Budi Santoso',
        'pembimbing_industri_email' => 'budi@contoh.co.id',
    ]);

    $this->get('/hubin/companies/'.$company->id.'/edit')
        ->assertOk()
        ->assertSee('Belum siap');
});
