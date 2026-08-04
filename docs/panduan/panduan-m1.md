# Panduan Implementasi — M1: Foundation & Auth

**Referensi:** `PRD.md` §3, §4, §5, §7, §8, §9 (BR31) | `TRD.md` §1, §2, §3, §5, §9, §10 | `milestone.md` M1

---

## 0. Prasyarat Sebelum Mulai

- [ ] `DESIGN.md` sudah final (warna, tipografi, komponen, pola navigasi) — **tidak boleh** mulai M1 sebelum ini beres, karena UI M1 harus langsung mengikuti `DESIGN.md`.
- [ ] Slot kerja M1 **khusus**: tech lead pegang **Backend Core + Integrasi&QA** sekaligus; 2 anggota lain **bersama-sama** mengisi slot **Frontend/UI**. Rotasi normal (1 orang/1 slot) baru mulai M2.

## 1. Tujuan Milestone

Membangun fondasi: struktur project, skema database awal, sistem role otomatis (bukan manual), autentikasi, dan layout dasar sesuai `DESIGN.md`.

## 2. Urutan Eksekusi Modul/Fitur

Ingat prinsip: **satu fitur tuntas end-to-end (backend → frontend → smoke test) sebelum pindah ke fitur berikutnya.**

### 2.1 Setup Project

- Laravel 13 + Inertia.js + React (TSX) + Tailwind CSS v4 + shadcn/ui.
- Pasang Lucide sebagai library ikon (bukan Tabler — keputusan khusus proyek ini).
- Siapkan struktur folder controller per role sejak awal meski masih kosong:
    ```
    app/Http/Controllers/Siswa/
    app/Http/Controllers/Kaprog/
    app/Http/Controllers/GuruPembimbing/
    app/Http/Controllers/Hubin/
    app/Policies/
    app/Observers/
    app/Notifications/
    ```
- Font: heading **Plus Jakarta Sans**, body **Lato**. Warna primary **Royal Blue #4169E1**, accent **Bright Orange #FFA500**, background **Bright Snow #FAFAFA** — ambil detail lengkap dari `DESIGN.md`.
- Smoke test: aplikasi jalan, halaman kosong render dengan Tailwind + shadcn/ui aktif.

### 2.2 Migration & Skema DB Awal

Tabel yang dibuat di tahap ini (lihat `TRD.md` §3.1):

- `users` — akun login (siswa & staf).
- `guru_profiles` — profil staf, kolom `jabatan` adalah sumber derivasi role.
- `siswa_profiles` — profil siswa, relasi ke `kompetensi_keahlian`.
- `kompetensi_keahlian` — master jurusan (dipakai ulang sebagai referensi "Jurusan", **bukan** tabel baru terpisah).

Buat seeder dasar:

- Akun per role (siswa, kaprog, guru pembimbing, hubin) dengan `jabatan` yang sesuai agar Observer (dibangun di 2.4) bisa langsung diverifikasi.
- Master `kompetensi_keahlian` sesuai jurusan riil sekolah.

Smoke test: migration jalan bersih, seeder mengisi data tanpa error, relasi FK `siswa_profiles → kompetensi_keahlian` valid.

### 2.3 Spatie `laravel-permission`

- Install & konfigurasi.
- Role yang didefinisikan: `siswa`, `kaprog`, `guru_pembimbing`, `hubin`.
- Belum perlu isi seluruh permission (daftar lengkap ada di `TRD.md` §5.1) — cukup role dasar dulu, permission granular ditambah incremental seiring modul yang membutuhkannya di M2+.

Smoke test: role bisa di-assign manual via tinker, `hasRole()` berfungsi.

### 2.4 Role Derivation Observer

- **Wajib** dibaca dulu: `TRD.md` §5 — role **tidak pernah** diset manual lewat UI, selalu derivasi dari `guru_profiles.jabatan` lewat Observer.
- Buat `app/Observers/GuruProfileObserver.php`, hook di event `saving`/`saved` pada `guru_profiles`.
- Logika: baca `jabatan`, sync role Spatie yang bersangkutan (`model_has_roles`).
- Ini juga fondasi untuk BR31 (M2): saat Hubin nanti menonaktifkan Kaprog, Observer yang sama yang menangani perubahan ke Guru Pembimbing — jadi pastikan Observer-nya generik terhadap semua kemungkinan perubahan `jabatan`, bukan hardcode satu skenario saja.

Smoke test:

- [ ] Ubah `jabatan` di `guru_profiles` lewat tinker → role Spatie ikut berubah otomatis.

### 2.5 Login & `CheckFirstLogin` Middleware

- Halaman login standar Inertia.
- Middleware `CheckFirstLogin` — bedakan skenario user baru vs user lama (detail perilaku spesifik ikuti `DESIGN.md`/kebutuhan sekolah, mis. paksa ganti password atau lengkapi profil di first login).

Smoke test:

- [ ] Login sukses untuk tiap role, diarahkan ke dashboard yang benar (dashboard boleh masih placeholder, yang penting redirect per-role benar).
- [ ] `CheckFirstLogin` membedakan user baru vs lama dengan benar.

### 2.6 Layout Dasar sesuai `DESIGN.md`

- Siswa: pola **mobile** — Bottom Nav + Hamburger Sheet + FAB kontekstual (model banking/task-intent, **bukan** overflow ala media sosial).
- Staf (Kaprog/Guru Pembimbing/Hubin): **sidebar desktop admin**.
- `Inertia::share` untuk data global (user, notifikasi, permission) **wajib** pakai closure `fn()` agar selalu fresh per-request — jangan pakai array statis.

Smoke test:

- [ ] Navigasi mobile muncul untuk role Siswa, sidebar muncul untuk role staf.
- [ ] Data user di `Inertia::share` selalu ter-update setelah perubahan (tidak stale/cache).

## 3. Checklist Testing Akhir Sesi M1

- [ ] Login tiap role berhasil dan diarahkan ke dashboard yang benar.
- [ ] Perubahan `jabatan` di `guru_profiles` otomatis mengubah role Spatie (verifikasi manual/tinker).
- [ ] `CheckFirstLogin` berjalan sesuai skenario (user baru vs lama).
- [ ] Navigasi sesuai `DESIGN.md` (mobile untuk Siswa, sidebar untuk staf).

**Semua item di atas harus lulus sebelum M2 dimulai.**
