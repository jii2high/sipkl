# Walkthrough SI-PKL — 00. Pendahuluan

> Dokumen ini adalah panduan membaca **kode yang sudah ada** (bukan panduan implementasi).
> Untuk panduan implementasi per milestone, lihat `docs/panduan/`.

## Apa itu SI-PKL?

**Sistem Informasi PKL (Praktik Kerja Lapangan)** untuk **SMKN 1 Kota Bekasi**.
Aplikasi web yang mendigitalisasi seluruh siklus PKL siswa:

```
pengajuan → verifikasi → persuratan → pembimbingan/monitoring → penilaian → selesai
```

Masalah yang dipecahkan: PKL sebelumnya dikelola manual — pengajuan kertas,
surat hilang, tidak ada status/history terpusat. Aplikasi ini mencatat **setiap
perubahan status** ke tabel riwayat (audit trail) dan mewajibkan alur yang
berurutan melalui aturan bisnis (BR = Business Rule).

### 4 Role Pengguna

| Role | Singkatan | Siapa |
|---|---|---|
| `siswa` | — | Siswa kelas XII yang melaksanakan PKL |
| `kaprog` | Kepala Program | Kepala kompetensi keahlian (jurusan); memverifikasi pengajuan |
| `guru_pembimbing` | GP | Guru pendamping siswa di lapangan (belum ada fitur aktifnya) |
| `hubin` | Hubungan Industri | Admin master data, persuratan, kelola akun |

## Stack Teknologi

| Lapisan | Teknologi |
|---|---|
| Backend | PHP 8.4, Laravel 13 |
| Auth backend | Laravel Fortify v1 |
| RBAC | `spatie/laravel-permission` v8 |
| Frontend | React 19 + TypeScript, Inertia.js v3 |
| Build | Vite 8, `@inertiajs/vite`, Wayfinder (typed routes) |
| UI | Tailwind CSS v4 + shadcn/ui (new-york), `lucide-react` |
| PDF | `barryvdh/laravel-dompdf` |
| Excel | `maatwebsite/excel` |
| Testing | Pest PHP v4 (+ PHPStan level 7, ESLint, Prettier, `tsc`) |
| Database | MySQL (target produksi), SQLite (default dev) |

Semua kode & komentar ditulis dalam **Bahasa Indonesia**. Tidak boleh ada tag
`<form>` HTML — semua form memakai Inertia `useForm` / `router.post`.

## Cara Menjalankan

```bash
# Install & setup sekali
composer install
composer setup            # bikin .env, key:generate, migrate, npm install, npm run build

# Menjalankan semua (server + queue + vite) sekaligus
composer dev              # php artisan serve + queue:listen + npm run dev (concurrently)

# Testing & quality
composer test             # config:clear → lint:check → types:check → pest
composer ci:check         # lint:check + format:check + types:check + test
npm run lint              # eslint . --fix
npm run types:check       # tsc --noEmit
```

> Dev DB default adalah SQLite (`database/database.sqlite`). Konfigurasi untuk
> test memakai SQLite `:memory:` (lihat `phpunit.xml`).

## Peta Direktori Level-1

```
sipkl/
├── app/                  # Kode PHP aplikasi
│   ├── Actions/Fortify/  # Custom action Fortify (reset password)
│   ├── Concerns/         # Trait validasi (password, profile)
│   ├── Exports/          # Export Excel (akun, template guru/siswa)
│   ├── Imports/          # Import Excel (guru, siswa)
│   ├── Http/
│   │   ├── Controllers/  # Controller per role: Auth/, Siswa/, Kaprog/, Hubin/, Settings/
│   │   ├── Middleware/   # CheckFirstLogin, HandleAppearance, HandleInertiaRequests
│   │   ├── Requests/     # Form Request (khusus Settings)
│   │   └── Responses/    # LoginResponse (redirect per role)
│   ├── Models/           # 12 model Eloquent
│   ├── Notifications/    # 5 notifikasi database
│   ├── Observers/        # GuruProfileObserver, PengajuanPklObserver
│   ├── Policies/         # PengajuanPklPolicy, SuratPengantarPolicy
│   ├── Providers/        # AppServiceProvider, FortifyServiceProvider
│   └── Support/          # PenomoranSurat (nomor surat sequential)
├── bootstrap/app.php     # Registrasi middleware, routing, exceptions
├── config/               # Konfigurasi Laravel (fortify, permission, excel, ...)
├── database/
│   ├── factories/        # Factory Eloquent (10 factory)
│   ├── migrations/       # 13 file migration → 24 tabel
│   └── seeders/          # Role/Permission/KompetensiKeahlian/DatabaseSeeder
├── docs/                 # PRD, TRD, milestone, panduan, diagrams, walkthrough (ini)
├── resources/
│   ├── css/app.css       # Tema Tailwind v4 (design token)
│   ├── js/               # Frontend React + Inertia (entry: app.tsx)
│   └── views/            # Blade: app.blade.php + template PDF surat
├── routes/
│   ├── web.php           # Semua route aplikasi
│   ├── settings.php      # Route profile/security/appearance
│   └── console.php
├── tests/                # Pest (Feature/ + Unit/)
└── vendor/, node_modules/ # Dependency (bukan bagian dari repo)
```

## Index Dokumen Walkthrough

| File | Isi |
|---|---|
| `00-pendahuluan.md` | (ini) pengenalan & peta |
| `01-arsitektur-dan-alur-request.md` | Boot aplikasi + siklus request end-to-end |
| `02-auth-m1.md` | Autentikasi (Fortify, first login, layout) |
| `03-master-data-hubin-m2.md` | CRUD master data & kelola akun + Excel |
| `04-pengajuan-verifikasi-m3.md` | Pengajuan siswa → verifikasi Kaprog |
| `05-persuratan-m4.md` | Surat Pengantar, Surat Balasan, nomor surat |
| `06-alur-utuh.md` | Satu contoh alur lengkap dari login → resmi PKL |
| `07-rbac-dan-policy.md` | Role/permission, route-level vs policy-level |
| `08-frontend-deep-dive.md` | React/Inertia, Wayfinder, komponen, desain |
| `09-testing-dan-ci.md` | Strategi test & perintah CI |
| `10-status-dan-tech-debt.md` | Status milestone & catatan teknis |
