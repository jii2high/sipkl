# Walkthrough SI-PKL — 02. Autentikasi (M1)

Modul ini = **cara login, logout, ganti password pertama kali, dan reset
password**. Backend auth memakai **Laravel Fortify**; frontend-nya adalah
halaman React di `resources/js/pages/auth/`.

---

## 2.1 Konfigurasi Fortify — `config/fortify.php`

```php
'home'      => '/dashboard',
'prefix'    => '',                     // route di level root (bukan /fortify/...)
'features'  => [
    Features::resetPasswords(),        // SATU-SATUNYA fitur aktif
],
'limiters'  => ['login' => 'login'],   // throttling login
```

Poin penting:

- **Hanya `resetPasswords`** yang diaktifkan. Registrasi publik, 2FA, update
  password mandiri, dan verifikasi email **dimatikan** — akun dibuat oleh Hubin
  via import/kelola akun (lihat `03-master-data`).
- Karena `prefix = ''`, route Fortify terdaftar di root: `/login`,
  `/forgot-password`, `/reset-password`, `/logout`, dst. Ini bisa dilihat di
  `php artisan route:list`.
- Login dibatasi **5 percobaan per menit** per email+IP (limiter `login`).

### Route Fortify (otomatis, tidak di `routes/web.php`)

| Method | URI | Controller (Fortify) |
|---|---|---|
| GET | `/login` | `AuthenticatedSessionController@create` |
| POST | `/login` | `@store` |
| POST | `/logout` | `@destroy` |
| GET/POST | `/forgot-password` | `PasswordResetLinkController` |
| GET/POST | `/reset-password` | `NewPasswordController` |
| GET | `/confirm-password` | `ConfirmablePasswordController` |
| GET | `/email/verify/*` | `EmailVerificationPromptController` |

Halaman React-nya ada di: `auth/login.tsx`, `auth/forgot-password.tsx`,
`auth/reset-password.tsx`, `auth/confirm-password.tsx`.

---

## 2.2 Login → Redirect per Role

Saat login berhasil, Fortify memanggil kelas yang mengimplementasikan
`LoginResponse` contract. Aplikasi ini menimpanya (custom) di:

### `app/Http/Responses/LoginResponse.php`

```php
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        return redirect()->route(match (true) {
            $request->user()->hasRole('siswa') => 'siswa.dashboard',
            $request->user()->hasRole('kaprog') => 'kaprog.dashboard',
            $request->user()->hasRole('hubin') => 'hubin.dashboard',
            default => 'guru-pembimbing.dashboard',
        });
    }
}
```

Binding-nya dilakukan di `AppServiceProvider`:

```php
$this->app->bind(LoginResponseContract::class, LoginResponse::class);
```

Jadi: setelah login, tiap role langsung dibawa ke **dashboard miliknya sendiri**.

---

## 2.3 Ganti Password Pertama Kali (First Login)

Setiap akun dibuat dengan **password default = NIS (siswa) / NIP (guru)** dan
flag `must_change_password = true` (kolom di tabel `users`). User **wajib**
mengganti password sebelum bisa mengakses aplikasi.

### Middleware `CheckFirstLogin` — `app/Http/Middleware/CheckFirstLogin.php`

```php
public function handle(Request $request, Closure $next): Response
{
    if ($request->user()?->must_change_password) {
        $allowed = ['password.force-change', 'password.force-change.update', 'logout'];

        if (! in_array($request->route()->getName(), $allowed)) {
            return redirect()->route('password.force-change');
        }
    }

    return $next($request);
}
```

- Jika user masih `must_change_password = true`, dia hanya boleh mengakses 3
  route: halaman ganti password, submit ganti password, dan logout.
- Route lain → di-redirect ke `password.force-change`.

### Controller — `app/Http/Controllers/Auth/FirstLoginController.php`

```php
$defaultPassword = $user->siswaProfile?->nis;   // NIS untuk siswa
$defaultPassword ??= $user->guruProfile?->nip;  // NIP untuk guru
```

Validasi (baris 25–36) memakai custom closure:

```php
function ($attribute, $value, $fail) use ($defaultPassword) {
    if ($defaultPassword && $value === $defaultPassword) {
        $fail('Password baru tidak boleh sama dengan password awal (NIS/NIP).');
    }
},
```

Aturan lengkap password: `Password::defaults()` (min. panjang + complexity,
diperketat di produksi oleh `AppServiceProvider`).

Setelah valid:

```php
$user->password = Hash::make($request->password);
$user->must_change_password = false;
$user->save();

return redirect()->route('dashboard');
```

`must_change_password` di-set false → user lolos `CheckFirstLogin` dan diarahkan
ke `/dashboard` (yang me-redirect ke dashboard per role).

> Route-nya (di `routes/web.php:17-20`) hanya ber-middlaware `auth` — bukan
> `check.first.login` — supaya user yang wajib ganti password tetap bisa
> mengakses halaman ini.

### Halaman frontend — `auth/first-login.tsx`

Form sederhana (password + konfirmasi + rules). Layout-nya `null` (full-screen,
tanpa sidebar).

---

## 2.4 Reset Password (Fortify)

- `GET /forgot-password` → form isi email.
- `POST /forgot-password` → kirim link reset (di env dev lewat log).
- `GET /reset-password/{token}` → form set password baru.
- `POST /reset-password` → jalankan `ResetUserPassword` action.

### Action custom — `app/Actions/Fortify/ResetUserPassword.php`

```php
class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function reset(User $user, string $password): void
    {
        $user->forceFill(['password' => Hash::make($password)])->save();
    }
}
```

Tidak men-set ulang `must_change_password` — reset password **tidak** mengubah
flag itu.

---

## 2.5 Layout Autentikasi (Frontend)

`app.tsx` meresolve semua halaman `auth/*` ke `AuthLayout`
(`resources/js/layouts/auth-layout.tsx`) yang memuat `auth/auth-simple-layout.tsx`:

- Stack logo (`AppLogo`), judul + deskripsi, dan form di tengah layar.
- Variant lain yang tersedia (tidak aktif): `auth-card-layout` (Card di tengah),
  `auth-split-layout` (2 kolom: panel brand gelap + form).

---

## 2.6 Ringkasan Alur Login

```
1. User akses / → middleware auth → redirect ke /login
2. POST /login (Fortify, throttle 5/menit)
3. LoginResponse → redirect per role (siswa/kaprog/hubin/guru_pembimbing)
4. CheckFirstLogin mengecek must_change_password:
   - true  → paksa ke /auth/force-change-password
   - false → lanjut ke dashboard role
5. POST /auth/force-change-password → password diganti, flag=false → dashboard
```

---

## 2.7 File Terkait

| File | Peran |
|---|---|
| `config/fortify.php` | Fitur & limiter auth |
| `bootstrap/app.php` | Alias middleware `check.first.login` |
| `app/Http/Responses/LoginResponse.php` | Redirect post-login per role |
| `app/Http/Middleware/CheckFirstLogin.php` | Gerbang ganti password wajib |
| `app/Http/Controllers/Auth/FirstLoginController.php` | Ganti password pertama |
| `app/Actions/Fortify/ResetUserPassword.php` | Proses reset password |
| `app/Providers/FortifyServiceProvider.php` | View Inertia untuk halaman Fortify + throttle |
| `resources/js/pages/auth/*.tsx` | Halaman login/reset/first-login/confirm |
| `resources/js/layouts/auth-*.tsx` | Layout autentikasi |
