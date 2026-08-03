# Walkthrough SI-PKL — 09. Testing & CI

Proyek memakai **Pest PHP v4** untuk test backend dan toolchain terpisah untuk
lint/typecheck frontend.

---

## 9.1 Perintah

| Perintah | Fungsi |
|---|---|
| `composer test` | `config:clear` → `lint:check` → `types:check` (PHPStan) → `php artisan test` |
| `php artisan test --compact` | Test cepat (hanya Pest) |
| `php artisan test --compact --filter=NamaTest` | Test satu file/kasus |
| `composer ci:check` | `npm lint:check` + `format:check` + `types:check` + test |
| `npm run lint` | `eslint . --fix` |
| `npm run types:check` | `tsc --noEmit` |
| `composer types:check` | PHPStan level 7 |

---

## 9.2 Setup Test — `tests/Pest.php`

```php
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)     // DB dibersihkan tiap test
    ->beforeEach(function () {
        // reset cache permission + bikin role & permission tiap test
    })
    ->in('Feature');
```

`beforeEach` meng-*seeding* seluruh permission & role (duplikasi manual dari
`PermissionSeeder`) agar helper di bawah bisa langsung dipakai. DB pakai
SQLite `:memory:` (lihat `phpunit.xml`).

### Helper yang tersedia

| Helper | Fungsi |
|---|---|
| `hubinUser()` | User ber-role `hubin` |
| `siswaUser()` | User siswa + `SiswaProfile` + KompetensiKeahlian |
| `kaprogUser()` | User + `GuruProfile` (jabatan `Kaprog`, dengan jurusan) |
| `asHubin()` / `asSiswa()` | `test()->actingAs(...)` |
| `siapkanSuratPengantar()` | Setup M4: periode aktif + perusahaan + pengajuan `disetujui` → return `compact('periode','perusahaan','user','pengajuan')` |

Contoh pemakaian:

```php
it('siswa bisa membuat pengajuan', function () {
    asSiswa();  // acting as siswaUser

    $this->post(route('siswa.pengajuan.store'), ['perusahaan_id' => ...])
        ->assertRedirect(...);
    $this->assertDatabaseHas('pengajuan_pkl', [...]);
});
```

---

## 9.3 Daftar Test (per file)

| File | Cakupan |
|---|---|
| `Unit/ExampleTest.php`, `Feature/ExampleTest.php` | Smoke test default |
| `Feature/DashboardTest.php` | Guest → redirect login; user → redirect dashboard per role |
| `Feature/Auth/AuthenticationTest.php` | Halaman login, alur login, 2FA redirect, password salah, logout, rate limit |
| `Feature/Auth/PasswordResetTest.php` | Request & proses reset password (token valid/invalid) |
| `Feature/Auth/PasswordConfirmationTest.php` | Halaman confirm-password + otorisasi |
| `Feature/Settings/ProfileUpdateTest.php` | Update profil, email-reverify, hapus akun |
| `Feature/Settings/SecurityTest.php` | Ganti password (current password benar/salah), gate confirm-password |
| `Feature/Hubin/PerusahaanTest.php` | CRUD + authz (guest redirect, non-hubin 403) |
| `Feature/Hubin/PeriodePklTest.php` | CRUD + authz periode |
| `Feature/Hubin/KompetensiKeahlianTest.php` | CRUD + authz jurusan |
| `Feature/Hubin/IndikatorPenilaianTest.php` | CRUD indikator + nested section |
| `Feature/Hubin/KelolaAkunTest.php` | List kaprog; **nonaktifkan kaprog → jabatan guru_pembimbing** (BR31) |
| `Feature/M4PersuratanTest.php` | Bundling authz/list, PDF cetak, kirim + **nomor sequential**, surat balasan: locked sampai pengantar ada (BR20), upload → **auto resmi_pkl** (BR7), non-PDF validation, tolak-balasan (status revert + delete record + notif + catatan ≥15) |

Pola umum test authz (satu fitur, dua arah):

```php
// boleh: sebagai hubin → sukses
// tidak boleh: guest → redirect login; role lain → 403
```

---

## 9.4 Strategi Yang Bisa Ditiru

1. **Setup helper di `Pest.php`** — hindari boilerplate bikin role/permission tiap test.
2. **Assert dua arah** — positif (yang berhak) & negatif (guest/403) untuk tiap endpoint.
3. **Test aturan bisnis** — BR7/BR17/BR20/BR31 punya test khusus:
   - BR31 → `KelolaAkunTest`
   - BR7/BR17/BR20 → `M4PersuratanTest`
4. **DB `:memory:`** → cepat, tanpa setup MySQL.

---

## 9.5 CI

`composer ci:check` menjalankan, secara berurutan:

1. `npm run lint:check` (ESLint)
2. `npm run format:check` (Prettier, cek saja)
3. `npm run types:check` (tsc)
4. `composer test` (lint pint → PHPStan → Pest)

> Pengingat dari konvensi boost: setelah mengubah file PHP, jalankan
> `vendor/bin/pint --dirty` agar format sesuai sebelum `types:check`/`test`.

---

## 9.6 File Terkait

| File | Peran |
|---|---|
| `tests/Pest.php` | Helpers + seeding RBAC tiap test |
| `tests/TestCase.php` | Base case + `skipUnlessFortifyHas()` |
| `tests/Feature/**` | Test fitur per modul |
| `tests/Unit/**` | Unit test (masih minimum) |
| `phpunit.xml` | SQLite `:memory:` + test suite |
| `phpstan.neon`, `pint.json`, `eslint.config.js`, `tsconfig.json` | Config quality gate |
