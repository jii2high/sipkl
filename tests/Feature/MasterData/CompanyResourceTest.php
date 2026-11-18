<?php

use App\Filament\Hubin\Resources\Companies\Pages\CreateCompany;
use App\Filament\Hubin\Resources\Companies\Pages\EditCompany;
use App\Filament\Hubin\Resources\Companies\Pages\ListCompanies;
use App\Models\Company;
use App\Models\User;
use App\Models\UserRole;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

function userResourceDenganRole(string $role): User
{
    $user = User::factory()->create();
    UserRole::create(['user_id' => $user->id, 'role' => $role]);

    return $user;
}

function loginResourceSebagai(string $role): User
{
    $user = userResourceDenganRole($role);

    test()->actingAs($user)->withSession(['active_role' => $role]);

    return $user;
}

beforeEach(function () {
    Filament::setCurrentPanel('hubin');
});

it('menampilkan daftar perusahaan untuk hubin', function () {
    loginResourceSebagai('hubin');

    Company::factory()->create(['nama' => 'PT Sinar Jaya Abadi']);

    $this->get('/hubin/companies')
        ->assertOk()
        ->assertSee('PT Sinar Jaya Abadi');
});

it('menolak daftar perusahaan untuk role non-hubin', function () {
    loginResourceSebagai('guru_pembimbing');

    $this->get('/hubin/companies')->assertForbidden();
});

it('membuat perusahaan dan menormalkan email, website, serta telepon', function () {
    loginResourceSebagai('hubin');

    livewire(CreateCompany::class)
        ->fillForm([
            'nama' => 'PT Contoh Sentosa',
            'alamat' => 'Jl. Merdeka No. 1, Bekasi',
            'email' => 'HRD@Contoh.co.id',
            'website' => 'https://www.contoh.co.id/',
            'telp_fax' => '(021) 555-0001',
            'source' => 'hubin',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $company = Company::query()->sole();

    expect($company->email)->toBe('hrd@contoh.co.id')
        ->and($company->website)->toBe('contoh.co.id')
        ->and($company->telp_fax)->toBe('0215550001');
});

it('menolak email perusahaan duplikat lewat form (AC-12)', function () {
    loginResourceSebagai('hubin');

    Company::factory()->create(['email' => 'hrd@contoh.co.id']);

    livewire(CreateCompany::class)
        ->fillForm([
            'nama' => 'PT Lain',
            'alamat' => 'Jl. Lain No. 2',
            'email' => 'HRD@Contoh.co.id',
            'source' => 'hubin',
        ])
        ->call('create')
        ->assertHasFormErrors(['email']);

    expect(Company::count())->toBe(1);
});

it('tetap menerima nama dan alamat yang mirip (soft warning M2.3)', function () {
    loginResourceSebagai('hubin');

    Company::factory()->create([
        'nama' => 'CV Ajikyo',
        'alamat' => 'Jl. Merdeka No. 1, Bekasi',
    ]);

    livewire(CreateCompany::class)
        ->fillForm([
            'nama' => 'CV Ajikiyo',
            'alamat' => 'Jl. Merdeka No. 2, Bekasi',
            'source' => 'hubin',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Company::count())->toBe(2);
});

it('menyimpan perubahan tanpa menabrak keunikan miliknya sendiri', function () {
    loginResourceSebagai('hubin');

    $company = Company::factory()->create([
        'nama' => 'PT Contoh Sentosa',
        'email' => 'hrd@contoh.co.id',
    ]);

    livewire(EditCompany::class, ['record' => $company->id])
        ->fillForm([
            'nama' => 'PT Contoh Sentosa Jaya',
            'alamat' => 'Jl. Baru No. 5',
            'email' => 'hrd@contoh.co.id',
            'source' => 'hubin',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($company->fresh()->nama)->toBe('PT Contoh Sentosa Jaya');
});

it('menolak viewAny perusahaan bagi role non-hubin di level policy', function () {
    loginResourceSebagai('guru_pembimbing');

    expect(Gate::allows('viewAny', Company::class))->toBeFalse();

    loginResourceSebagai('hubin');

    expect(Gate::allows('viewAny', Company::class))->toBeTrue();
});

it('menggabungkan perusahaan lewat aksi tabel', function () {
    loginResourceSebagai('hubin');

    $target = Company::factory()->create(['nama' => 'PT Tujuan']);
    $sumber = Company::factory()->create(['nama' => 'PT Sumber']);

    livewire(ListCompanies::class)
        ->callAction(TestAction::make('merge')->table($sumber), [
            'target_id' => $target->id,
            'alasan' => 'Duplikat',
        ])
        ->assertNotified();

    expect($sumber->fresh()->merged_into_id)->toBe($target->id);
});
