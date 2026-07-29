# Panduan Implementasi — M7: Deployment & UAT

**Referensi:** `PRD.md` (seluruh alur bisnis §5, semua BR) | `TRD.md` (seluruh dokumen sebagai acuan regresi) | `milestone.md` M7

---

## 0. Prasyarat

- [ ] Checklist M1–M6 semua sudah lulus. **Tidak boleh** ada fitur atau komponen UI baru yang masih dikerjakan.

## 1. Tujuan Milestone

**Murni** deployment dan User Acceptance Testing. **Tidak ada** fitur atau komponen UI baru di milestone ini — jika ditemukan kebutuhan fitur baru selama UAT, itu dicatat sebagai backlog di luar M7, bukan dikerjakan langsung di sini.

## 2. Urutan Eksekusi

### 2.1 Setup Environment Production/Staging
- Siapkan server/hosting untuk staging dan production secara terpisah.
- Konfigurasi environment variables (`.env`) sesuai masing-masing environment.

### 2.2 Migration & Seeding Data Production-Ready
- Jalankan migration bersih di staging/production.
- Seeder yang dipakai di sini **berbeda** dari seeder development (M1/M9-TRD) — pastikan hanya data master riil sekolah (jurusan, akun awal staf sesuai `jabatan` riil) yang masuk, bukan data dummy testing.

### 2.3 Regression Test End-to-End (M1–M6) di Staging
Lakukan regresi menyeluruh terhadap **seluruh alur utama**, bukan per-modul terpisah — telusuri sebagai satu alur berkesinambungan:
1. Login tiap role → M1.
2. Master data & Kelola Akun Kaprog → M2.
3. Pengajuan PKL → verifikasi Kaprog → Riwayat PKL → M3.
4. Surat Pengantar → Surat Balasan → Resmi PKL → Bundling & Cetak → M4.
5. Assign Guru Pembimbing (termasuk self-assign) → Surat Tugas Monitoring (Global/Scoped) → Laporan Kunjungan → M5.
6. Laporan Akhir → Formulir Penilaian → Nilai Akhir → Selesai PKL → M6.

Untuk tiap alur, verifikasi ulang butir-butir Business Rules kritikal berikut (rawan regresi karena saling bergantung antar milestone):
- BR1 (satu pengajuan aktif), BR6 (audit trail lengkap), BR7 (auto Resmi PKL), BR9 (guard sebelum persuratan).
- BR13 (auto Selesai PKL), BR20 (lock Surat Balasan), BR22 (gate Laporan Kunjungan), BR28 (kalkulasi Nilai Akhir dua skenario), BR31 (Observer role saat nonaktifkan Kaprog), BR32 (gate Formulir Penilaian).

### 2.4 UAT Bersama Pembimbing/Pihak Sekolah
- Jadwalkan sesi UAT dengan pembimbing PKL/pihak sekolah (Hubin, Kaprog, Guru Pembimbing riil jika memungkinkan).
- Kumpulkan feedback terstruktur (bug vs permintaan fitur baru — pisahkan keduanya sejak awal).

### 2.5 Perbaikan Bug Hasil UAT (Bukan Fitur Baru)
- Perbaiki hanya bug yang ditemukan selama UAT.
- Permintaan fitur baru dari UAT **dicatat sebagai backlog**, tidak dikerjakan di M7 (sesuai prinsip milestone: fokus satu milestone, dan M7 murni deployment).

### 2.6 Deployment ke Production
- Setelah sign-off UAT didapat dan bug kritikal selesai diperbaiki, deploy ke production.
- Siapkan backup & rollback plan sebelum go-live.

## 3. Checklist Testing Akhir Sesi M7

- [ ] Seluruh alur utama (pengajuan → persuratan → monitoring → penilaian → selesai) berjalan mulus di production/staging.
- [ ] Tidak ada regresi dari fitur M1–M6.
- [ ] Sign-off UAT dari pembimbing/pihak sekolah didapatkan.
- [ ] Backup & rollback plan tersedia sebelum go-live.

**Setelah seluruh checklist ini lulus, proyek SI-PKL siap go-live.**
