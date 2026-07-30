# Project Timeline & Milestone
## Sistem Informasi PKL (SI-PKL) — SMKN 1 Kota Bekasi

**Versi:** 1.1 (final)
**Tim:** 3 orang, jam kerja 09.00–16.00 (mengikuti jam PKL)
**Prinsip kerja:** fokus 1 milestone sampai selesai (bukan paralel banyak milestone), role dalam milestone dirotasi antar 3 anggota tiap milestone (bukan spesialisasi permanen), kecuali M0–M1 (lihat catatan khusus).
**Prinsip UI:** `DESIGN.md` final sebelum M0 dimulai; UI dibangun sesuai `DESIGN.md` sejak M0, paralel dengan backend di tiap milestone — **tidak ada** overhaul UI di akhir proyek.
**Eksekusi kode:** implementasi/eksekusi dilakukan **per modul/fitur**, bukan sekaligus satu milestone — setiap fitur di dalam sebuah milestone diselesaikan end-to-end (backend → frontend → smoke test) sebelum berpindah ke fitur berikutnya dalam milestone yang sama.

---

## Slot Kerja per Milestone

Setiap milestone dibagi 3 slot: **Backend Core**, **Frontend/UI**, **Integrasi & QA**. Rotasi normal = 1 orang per slot, berganti tiap milestone.

**Catatan khusus M0–M1:** tech lead memegang slot Backend Core + Integrasi&QA sekaligus; 2 anggota lain bersama-sama mengisi slot Frontend/UI. Rotasi normal (1 orang 1 slot) kembali berlaku mulai M2.

---

## M1 — Foundation & Auth

**Tujuan:** fondasi database, autentikasi, dan derivasi role otomatis.

Modul/fitur (dieksekusi berurutan):
1. Setup project Laravel 13 + Inertia + React + Tailwind v4 + shadcn/ui.
2. Migration & skema DB awal (users, guru_profiles, siswa_profiles, kompetensi_keahlian) + seeder dasar.
3. Instalasi & konfigurasi Spatie `laravel-permission` (roles: siswa, kaprog, guru_pembimbing, hubin).
4. **Role Derivation Observer** — derivasi role otomatis dari `guru_profiles.jabatan`.
5. Login & `CheckFirstLogin` middleware.
6. Layout dasar sesuai `DESIGN.md` (navigasi mobile Siswa, sidebar desktop staf).

**Checklist testing akhir sesi M1:**
- [ ] Login tiap role berhasil dan diarahkan ke dashboard yang benar.
- [ ] Perubahan `jabatan` di `guru_profiles` otomatis mengubah role Spatie (verifikasi manual/tinker).
- [ ] `CheckFirstLogin` berjalan sesuai skenario (user baru vs lama).
- [ ] Navigasi sesuai `DESIGN.md` (mobile untuk Siswa, sidebar untuk staf).

---

## M2 — Master Data & User Management (Hubin)

**Tujuan:** seluruh master data yang jadi dependency modul berikutnya, plus komponen Search & Filter Bar reusable.

Modul/fitur:
1. CRUD Perusahaan.
2. CRUD Periode PKL.
3. CRUD Kompetensi Keahlian (Jurusan) — kolom nama, acronym.
4. CRUD Indikator Penilaian + Section Penilaian (nullable, child).
5. Kelola Akun (Hubin): tabel Kelola Akun Kaprog dengan aksi **"Nonaktifkan sebagai Kaprog"** (bukan dropdown Ubah Jabatan bebas).
6. Import/Export Excel untuk data master.
7. **Search & Filter Bar** — komponen global reusable, dipasang di seluruh modul list/table (BR21).

**Checklist testing akhir sesi M2:**
- [ ] CRUD tiap master data berjalan dengan validasi Bahasa Indonesia.
- [ ] Aksi "Nonaktifkan sebagai Kaprog" memicu Observer M1 dan mengubah role jadi Guru Pembimbing.
- [ ] Import/Export Excel berhasil round-trip tanpa korupsi data.
- [ ] Search & Filter Bar berfungsi di minimal 2 modul berbeda.

---

## M3 — Pengajuan & Verifikasi Awal

**Tujuan:** alur pengajuan PKL siswa sampai verifikasi Kaprog, sekaligus titik pertama infrastruktur audit trail dan notifikasi.

Modul/fitur:
1. Form Pengajuan PKL (Siswa).
2. Halaman list pengajuan (Kaprog) dengan ghost button "Lihat detail".
3. Halaman detail pengajuan — approve/reject di halaman detail (bukan di list).
4. **`pengajuan_pkl_histories`** — audit trail perubahan status (infra pertama kali dibangun di sini).
5. **Notification infra** — Laravel DB Notification + Sonner (infra pertama kali dibangun di sini).
6. Riwayat PKL siswa (termasuk pengajuan periode sebelumnya / PKL ulang, BR14).

**Checklist testing akhir sesi M3:**
- [ ] Siswa dapat submit pengajuan dan melihat statusnya berubah real-time.
- [ ] Approve/reject Kaprog tercatat di `pengajuan_pkl_histories`.
- [ ] Notifikasi in-app muncul saat status pengajuan berubah.
- [ ] PKL ulang (submit ulang lewat form biasa) muncul benar di Riwayat PKL, tidak merusak data pengajuan aktif.

---

## M4 — Persuratan

**Tujuan:** seluruh alur surat dari Surat Pengantar sampai Resmi PKL.

Modul/fitur:
0. **Prasyarat — Rework M3**: migrasi `PengajuanState` (spatie/laravel-model-states) ke kolom `status` enum biasa + validasi Policy/Form Request; migrasi collection spatie/laravel-medialibrary (`surat_balasan_perusahaan`, `laporan_akhir_pkl`, `dokumen_pendukung`) ke kolom path biasa. Dikerjakan tuntas sebelum modul M4 di bawah dimulai, supaya M4 dst konsisten satu pola.
1. Generasi PDF Surat Pengantar (dengan penomoran surat, pakai `barryvdh/laravel-dompdf` atau setara — bukan `spatie/laravel-pdf`).
2. Upload Surat Balasan (Siswa) — **conditional lock**, terbuka hanya setelah Surat Pengantar terbit (BR20, dicek lewat keberadaan record `surat_pengantar`, bukan kolom status baru).
3. Validasi sistem atas file Surat Balasan (format PDF, terbaca/tidak corrupt) → **auto status Resmi PKL** begitu lolos (BR7). Hubin tetap bisa menolak balasan pasca-fakta (`surat-balasan.verifikasi`, catatan ≥15 karakter) bila dokumen ternyata tidak sesuai — status kembali ke Disetujui.
4. Fitur **Bundling & Cetak** (Hubin) — preview dokumen + tombol Cetak/Kirim terpisah, plus tab **Balasan Masuk** di halaman yang sama untuk aksi tolak pasca-fakta.
5. Notifikasi terkait persuratan (surat terbit, balasan lolos validasi, balasan ditolak Hubin, dst).

**Checklist testing akhir sesi M4:**
- [ ] Rework M3 tuntas — tidak ada lagi pemanggilan `spatie/laravel-model-states` atau `spatie/laravel-medialibrary` di codebase.
- [ ] Upload Surat Balasan benar-benar terkunci sebelum Surat Pengantar terbit.
- [ ] Status siswa otomatis Resmi PKL setelah Surat Balasan lolos validasi sistem (bukan setelah verifikasi manual).
- [ ] File Surat Balasan corrupt/tidak valid ditolak validasi, belum mengubah status.
- [ ] Hubin bisa menolak balasan pasca-fakta di tab Balasan Masuk, status kembali ke Disetujui.
- [ ] PDF Surat Pengantar tergenerate dengan nomor surat unik dan format sesuai template (dompdf).
- [ ] Tombol Cetak dan Kirim di Bundling berfungsi independen (preview tidak auto-kirim).

---

## M5 — Pembimbingan & Kunjungan Monitoring

**Tujuan:** assignment Guru Pembimbing dan alur monitoring kunjungan berbasis Surat Tugas.

Modul/fitur:
1. Assign Guru Pembimbing (Kaprog) — tabel per baris (Siswa, Perusahaan, dropdown Guru Pembimbing, Simpan/Ubah), wewenang eksklusif Kaprog (BR10).
2. Self-assign Kaprog sebagai pembimbing — section "Bimbingan Saya" + permission `kunjungan.*`.
3. Surat Tugas Monitoring — **Global** (Hubin, seluruh jurusan) dan **Scoped** (Hubin, atas request Kaprog jurusan tsb) (BR22, BR23).
4. Laporan Kunjungan Monitoring (Guru Pembimbing) — gated oleh Surat Tugas Monitoring, diisi berkala/dicicil.
5. Notifikasi terkait assignment dan kunjungan baru.

**Checklist testing akhir sesi M5:**
- [ ] Hanya Kaprog jurusan terkait yang bisa assign Guru Pembimbing untuk siswanya.
- [ ] Kaprog self-assign muncul benar di "Bimbingan Saya" tanpa mengubah role Spatie-nya.
- [ ] Laporan Kunjungan tidak bisa diisi tanpa Surat Tugas Monitoring aktif (Global atau Scoped).
- [ ] Surat Tugas Scoped hanya berlaku untuk jurusan yang di-request, tidak bocor ke jurusan lain.

---

## M6 — Penilaian Akhir & Selesai PKL

**Tujuan:** alur penilaian akhir sampai penutupan status PKL siswa.

Modul/fitur:
1. Upload Laporan Akhir PKL (Siswa).
2. **Formulir Penilaian PKL** (Guru Pembimbing) — gated oleh Laporan Akhir siswa (BR32), diisi sekali menjelang sidang, termasuk field sakit/izin/alpa, catatan guru pembimbing, nama pembimbing industri.
3. Input nilai per Indikator/Section (Umum & Teknis Kejuruan) dengan skala predikat K/C/B/SB (fixed: <70/70–80/81–90/91–100).
4. **Kalkulasi Nilai Akhir otomatis** — rata-rata seluruh leaf indikator/section yang terisi (BR28).
5. **Auto status Selesai PKL** setelah Nilai Akhir terisi (BR13).
6. Notifikasi terkait Formulir Penilaian terbuka dan Selesai PKL.

**Checklist testing akhir sesi M6:**
- [ ] Formulir Penilaian benar-benar terkunci sebelum Laporan Akhir diupload siswa.
- [ ] Formulir hanya bisa disubmit sekali (tidak bisa diisi ulang bebas).
- [ ] Nilai Akhir terhitung benar untuk kasus jurusan dengan sub-indikator maupun tanpa sub-indikator.
- [ ] Status siswa otomatis berubah jadi Selesai PKL setelah submit penilaian.

---

## M7 — Deployment & UAT

**Tujuan:** murni deployment dan User Acceptance Testing — **tidak ada** fitur atau komponen UI baru di milestone ini.

Modul/fitur:
1. Setup environment production/staging.
2. Migration & seeding data production-ready.
3. Regression test end-to-end seluruh alur (M1–M6) di environment staging.
4. UAT bersama pembimbing/pihak sekolah.
5. Perbaikan bug hasil UAT (bukan fitur baru).
6. Deployment ke production.

**Checklist testing akhir sesi M7:**
- [ ] Seluruh alur utama (pengajuan → persuratan → monitoring → penilaian → selesai) berjalan mulus di production/staging.
- [ ] Tidak ada regresi dari fitur M1–M6.
- [ ] Sign-off UAT dari pembimbing/pihak sekolah didapatkan.
- [ ] Backup & rollback plan tersedia sebelum go-live.

---

## Catatan Eksekusi

- Search & Filter Bar dan trigger notifikasi baru ditambahkan **inkremental** di tiap milestone M2–M6 sesuai fitur yang relevan — tidak dikumpulkan di M7.
- Setiap fitur dalam satu milestone diselesaikan tuntas (backend, frontend sesuai `DESIGN.md`, smoke test) sebelum tim berpindah ke fitur berikutnya di milestone yang sama.
- Checklist testing akhir sesi tiap milestone wajib lulus sebelum milestone berikutnya dimulai.
