# Walkthrough SI-PKL — 01. Arsitektur & Alur Request

Dokumen ini menjelaskan **apa yang terjadi dari user mengetik URL sampai halaman
tampil** (dan dari klik tombol sampai data tersimpan). Ada dua arah aliran yang
perlu dipahami:

1. **Boot frontend** — bagaimana React/Inertia dimuat.
2. **Siklus request backend** — bagaimana route → middleware → controller →
   policy → model bekerja.

---

## 1.1 Boot Frontend (Halaman Pertama)

Semua aplikasi Inertia dimulai dari **satu file Blade** sebagai "cangkang".

### `resources/views/app.blade.php`

```blade
<html lang="id" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
```

- `$appearance` berasal dari cookie `appearance` (dibagikan oleh middleware
  `HandleAppearance`). Nilai: `light`, `dark`, atau `system`.
- **Script inline** (baris 8–20) membaca `prefers-color-scheme` untuk menempelkan
  kelas `dark` ke `<html>` **sebelum** React load — mencegah "flash" tema.
- **Google Fonts** (baris 37): `Lato` (body) + `Plus Jakarta Sans` (heading),
  dua-duanya wajib per DESIGN.md.
- `@vite([... 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])`
  — memuat entry app + **chunk per halaman** (hanya halaman aktif yang di-load).
- `<x-inertia::app />` — komponen Inertia yang menampilkan halaman pertama.

### `resources/js/app.tsx` — entry React

```tsx
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':           return null;
            case name.startsWith('auth/'):     return AuthLayout;
            case name.startsWith('siswa/'):    return AppLayout;  // ponytail: layout mobile menunggu spec designer
            case name.startsWith('settings/'): return [AppLayout, SettingsLayout];
            default:                           return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />   {/* sonner toast global */}
            </TooltipProvider>
        );
    },
    progress: { color: '#4B5563' },  // progress bar Inertia (gray-600)
});

initializeTheme();  // set light/dark dari localStorage/cookie/prefers-color-scheme
```

Poin penting:

- **`layout` resolver**: fungsi yang memilih layout berdasarkan nama komponen.
  Halaman bisa mendeklarasikan layout sendiri lewat `Page.layout` (lihat `08-frontend`).
  `siswa/*` **sengaja** masih memakai `AppLayout`; layout mobile
  (`resources/js/layouts/siswa-layout.tsx`) sudah dibuat tapi **belum diaktifkan**
  (ditunggu spec designer — komentar `ponytail:` di baris 20).
- **`strictMode: true`** → React Strict Mode aktif (komponen dirender dua kali
  di dev untuk deteksi bug).
- **`withApp`** membungkus semua halaman dengan `TooltipProvider` + `Toaster`.

### Arah aliran render

```
Blade (app.blade.php)
   │  @vite memuat JS + page chunk
   ▼
app.tsx → createInertiaApp
   │  layout resolver memilih layout
   ▼
[Layout] + [PageComponent]  → React DOM render
   ▲
   └── props dari controller via Inertia::render('path/ke/page', [...])
```

---

## 1.2 Siklus Request Backend

Ini alur yang terjadi **setiap kali** browser meminta halaman atau mengirim
formulir:

```
URL → routes/ → middleware global → middleware route → Controller
        → Policy (authorize) → Model/DB → Inertia::render / redirect
```

### `bootstrap/app.php` — pusat konfigurasi Laravel 13

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->web(append: [HandleAppearance, HandleInertiaRequests, AddLinkHeaders]);
        $middleware->alias([
            'check.first.login' => CheckFirstLogin::class,
            'permission'        => PermissionMiddleware::class,   // Spatie
            'role'              => RoleMiddleware::class,         // Spatie
            'role_or_permission'=> RoleOrPermissionMiddleware::class,
        ]);
    });
```

Yang perlu diperhatikan:

- **Cookie yang TIDAK dienkripsi**: `appearance` dan `sidebar_state` — keduanya
  dibaca langsung oleh JS (tema) / Inertia (state sidebar).
- **Alias middleware**: `check.first.login` (custom) dan `permission`/`role`/
  `role_or_permission` (dari Spatie). Ini dipakai di `routes/web.php`.
- Cookie lain tetap dienkripsi oleh Laravel (keamanan default).

### Lapisan middleware per request

Urutan untuk route dalam grup `auth, verified, check.first.login`:

1. `web` (global) → session, CSRF, dll.
2. `HandleAppearance` → share cookie `appearance` ke view.
3. `HandleInertiaRequests` → **shared props** Inertia (lihat bawah).
4. `AddLinkHeadersForPreloadedAssets` → `Link` header untuk preload asset Vite.
5. `auth` → pastikan login.
6. `verified` → email terverifikasi.
7. `check.first.login` → paksa ganti password (lihat `02-auth`).
8. `permission:<nama-perm>` → pastikan user punya permission (Spatie).

### `routes/web.php` — peta route

```php
Route::inertia('/', 'welcome')->name('home');                     // publik

Route::middleware(['auth'])->group(fn () =>                        // hanya login
    // auth/force-change-password GET|POST → FirstLoginController
);

Route::middleware(['auth', 'verified', 'check.first.login'])->group(...)
    // dashboard + seluruh halaman aplikasi, dikelompokkan per prefix role:
    //   siswa.  → pengajuan, riwayat, surat-balasan
    //   kaprog. → verifikasi (index, show, approve, reject)
    //   guru-pembimbing. → dashboard (placeholder)
    //   hubin.  → master data, kelola-akun, surat-pengantar
```

Cara paling cepat melihat semua route:

```bash
php artisan route:list
```

Contoh route dengan proteksi dua lapis (route + policy):

```php
Route::get('pengajuan', [SiswaPengajuanController::class, 'index'])
    ->name('pengajuan.index')
    ->middleware('permission:pengajuan.ajukan');   // lapis 1: permission
```

Di dalam controller ada lagi `$this->authorize(...)` → **lapis 2: policy**.

### `HandleInertiaRequests` — shared props

Semua halaman otomatis menerima props ini (selalu fresh via closure):

```php
'auth' => [
    'user'  => fn () => $request->user(),          // data user (model, di-serialize Inertia)
    'roles' => fn () => $request->user()?->getRoleNames()?->toArray() ?? [],
],
'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
'notifikasi_belum_dibaca' => fn () => $request->user()?->unreadNotifications()->count() ?? 0,
'notifikasi_terbaru'      => fn () => $request->user()?->notifications()->latest()->take(5)->get() ?? [],
```

- **Closure wajib** (`fn () =>`) — sesuai TRD §2, supaya nilainya dihitung per
  request, tidak di-cache di seluruh session.
- `auth.user` otomatis memuat relasi `guruProfile` & `siswaProfile` karena model
  `User` memakai `protected $with = ['guruProfile', 'siswaProfile']`.

---

## 1.3 Lapisan Controller → Policy → Model

### Controller

- Semua controller mewarisi `App\Http\Controllers\Controller` (abstrak, hanya
  `use AuthorizesRequests`).
- **Namespace per role**: `Siswa/`, `Kaprog/`, `Hubin/`, `Settings/`, `Auth/` —
  ini membuat "siapa pemilik fitur ini" terlihat dari path.
- Dua pola respons:
  - **Halaman**: `return Inertia::render('path/ke/page', $props)` — `Inertia\Response`.
  - **Aksi (POST/PUT/DELETE)**: `return redirect()->route(...)` — `RedirectResponse`,
    biasanya dengan `->with('sukses', 'pesan')` (flash → toast via `use-flash-toast`).

### Policy — otorisasi data

```php
$this->authorize('create', PengajuanPkl::class);   // di controller
```

- Policy dipakai untuk aturan **berbasis data** (misal: "hanya pemilik", "hanya
  sejurusan", "hanya kalau status X"). Bedanya dengan middleware `permission`:
  permission hanya cek "punya izin tidak", policy cek "berhak atas **objek ini** tidak".
- Daftar policy: `PengajuanPklPolicy`, `SuratPengantarPolicy`. Detail di `07-rbac`.

### Model

- 12 model Eloquent di `app/Models/`. Relasi + aksesor menentukan bentuk data
  yang dikirim ke frontend.
- Contoh aksesor yang penting:
  ```php
  // app/Models/PengajuanPkl.php:93
  public function getExistsSuratPengantarAttribute(): bool
  {
      return $this->suratPengantars()->exists();
  }
  ```
  Model ini `$appends = ['exists_surat_pengantar']`, jadi frontend selalu dapat
  boolean `exists_surat_pengantar` — dipakai halaman siswa untuk memutuskan
  apakah tombol upload surat balasan boleh muncul.

### Notifikasi

- Memakai channel `database` (tabel `notifications`). 5 notifikasi di
  `app/Notifications/`, semuanya `implements ShouldQueue` (antrian → `queue:listen`
  ikut berjalan di `composer dev`).
- Ditampilkan lewat shared prop `notifikasi_terbaru` + badge
  `notifikasi_belum_dibaca` di header/sidebar.

---

## 1.4 Alur Data Halaman (Contoh: Daftar Pengajuan Siswa)

`GET /siswa/pengajuan`:

1. Route `siswa.pengajuan.index` dijalankan — middleware `auth, verified,
   check.first.login, permission:pengajuan.ajukan`.
2. `Siswa\PengajuanController@index`:
   - Ambil `siswa_id` dari `request()->user()->siswaProfile->id`.
   - Cari periode aktif (`status_aktif = true`).
   - Cari pengajuan siswa pada periode itu yang **bukan** `ditolak`.
   - `Inertia::render('siswa/pengajuan/index', [...])`.
3. Inertia mengirim JSON (data + component name + props) ke browser.
4. `app.tsx` resolver memilih layout → `siswa/pengajuan/index.tsx` dirender
   dengan props `pengajuan`, `perusahaan`, `periodeAktif`.

> Konsep kunci Inertia: **tidak ada** SPA "API terpisah". Setiap navigasi = request
> HTTP penuh ke Laravel, tapi hanya **data (JSON)** yang dikirim, bukan HTML.
> React merender ulang tanpa reload penuh.
