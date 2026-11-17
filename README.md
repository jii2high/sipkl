# SIPKL — Sistem Informasi Manajemen PKL SMKN 1 Kota Bekasi

Aplikasi digitalisasi alur PKL: pengajuan → persuratan → bimbingan → pengesahan laporan (5-TTD) → sidang → penilaian, dengan status real-time per peran dan mekanisme anti-manipulasi (kunci nilai, token ter-hash, snapshot immutable, audit log).

Dokumen acuan (urutan prioritas):

1. `docs/PRD.md` — sumber kebenaran perilaku produk.
2. `docs/TASKS.md` — pecahan milestone + aturan eksekusi.
3. `docs/schema.sql` — rancangan referensi MySQL (bukan script yang dijalankan langsung).
4. `AGENTS.md` — konteks kerja untuk AI agent.

## Versi terpasang (M0.1–M0.11)

| Komponen | Versi |
|---|---|
| PHP | 8.4.13 |
| Laravel | 13.42.0 |
| Filament | 5.11.4 (panel `hubin`, `app`) |
| Livewire | 4.4.8 |
| Pest | 4.7.8 (+ pest-plugin-laravel, pest-plugin-livewire) |
| spatie/laravel-activitylog | 5.1.5 |
| barryvdh/laravel-dompdf | 3.1.2 (dompdf) |
| endroid/qr-code | 6.1.3 |
| maatwebsite/excel | 4.0.6 |
| Composer / Node.js | 2.9.5 / 26.5.0 |
| Database dev | MariaDB 10.4.32 (XAMPP, port 3306, DB `sistem_pkl`) |

Stack yang dikunci (PRD §1.4): Laravel + Filament multi-panel (`/hubin`, `/app`) + Blade/Livewire, MySQL, queue/cache/session driver `database`, Pest untuk test. Jangan menambah paket/abstraksi di luar daftar `TASKS.md` tanpa task.

## Setup lokal

```powershell
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
php artisan filament:assets
php artisan serve
```

Buka `http://localhost:8000/hubin` (Hubin) atau `http://localhost:8000/app` (role lain). Panels memakai `active_role` di sesi; halaman login kustom dibangun di M1 (`/login` masih placeholder).

Akun dev (password awal = NIS/NIP):

| Akun | Email | Role |
|---|---|---|
| Hubin | `hubin001@smkn1kotabekasi.sch.id` | hubin |
| Kepsek | `kepsek001@smkn1kotabekasi.sch.id` | kepsek |
| Kaprog RPL | `kaprpl01@smkn1kotabekasi.sch.id` | kaprog (RPL) |
| Walas XI RPL 1 | `walrpl11@smkn1kotabekasi.sch.id` | walas |
| Guru + Walas XI RPL 2 | `guru001@smkn1kotabekasi.sch.id` | guru_pembimbing, walas |
| Siswa ×6 | `siswa001` … `siswa006` @smkn1kotabekasi.sch.id | siswa |

## Catatan database

PRD §1.4 mengunci **MySQL 8.0.16+**. Lingkungan dev saat ini memakai **MariaDB 10.4.32** (server XAMPP yang sudah dipakai), dengan dua penyesuaian agar skema tetap jalan:

1. Kolom `TIMESTAMP NOT NULL` dari `schema.sql` dipetakan ke `DATETIME NOT NULL` (`explicit_defaults_for_timestamp=OFF` di MariaDB; DATETIME juga tidak dikonversi zona waktu server sehingga benar-benar UTC).
2. `activity_log` mendapat kolom `attribute_changes` yang diwajibkan spatie/laravel-activitylog v5 (tidak ada di `schema.sql`).

Sebelum go-live, migrasi dan constraint wajib diuji ulang di MySQL 8.

## Test

```powershell
php vendor/bin/pest
```

Test memakai DB `sistem_pkl_test` (lihat `phpunit.xml`). Buat DB-nya sekali dengan `CREATE DATABASE sistem_pkl_test`.
