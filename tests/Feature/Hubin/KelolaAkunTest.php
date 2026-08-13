<?php

use App\Models\GuruProfile;
use App\Models\User;

test('guests redirected to login', function () {
    $this->get(route('hubin.kelola-staff.index'))->assertRedirect(route('login'));
});

test('non-hubin gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('hubin.kelola-staff.index'))->assertForbidden();
});

test('index returns staff list', function () {
    asHubin();
    User::factory()->count(3)->create()->each(fn ($u) => GuruProfile::factory()->create(['user_id' => $u->id, 'jabatan' => 'kaprog'])
    );
    $this->get(route('hubin.kelola-staff.index'))->assertOk();
});

test('nonaktifkan kaprog changes jabatan to guru_pembimbing', function () {
    asHubin();
    $kaprog = GuruProfile::factory()->create(['jabatan' => 'kaprog']);
    $this->patch(route('hubin.kelola-staff.nonaktifkan', $kaprog))->assertRedirect();
    $this->assertDatabaseHas('guru_profiles', ['id' => $kaprog->id, 'jabatan' => 'guru_pembimbing']);
});
